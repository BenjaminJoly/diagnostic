<?php
namespace Diagnostic\Service;
use PhpOffice\PhpWord\TemplateProcessor;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\Session\Container;


/**
 * Template Processor Service
 *
 * @package Diagnostic\Service
 * @author Jerome De Almeida <jerome.dealmeida@vesperiagroup.com>
 */
class TemplateProcessorService extends TemplateProcessor implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Set a new image
     *
     * @param string $search
     * @param string $replace
     */
    public function setImageValue($search, $replace)
    {
        // Sanity check
        if (!file_exists($replace))
        {
            return;
        }

        // Delete current image
        $this->zipClass->deleteName('word/media/' . $search);

        // Add a new one
        $this->zipClass->addFile($replace, 'word/media/' . $search);
    }

    /**
     * Generate word
     *
     * @param $data
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    public function generateWord($data, $questions, $results, $information, $translator) {

        $data['date'] = date('Y/m/d');

        $filename = ucfirst($data['document']) . '_' . date('Y-m-d') . '.docx';
        $filepath = 'data/results/' . $filename;

        //retrieve categories
        $numberByCategories = [];
        foreach ($questions as $question) {
            if (array_key_exists($question->getCategoryTranslationKey(), $numberByCategories)) {
                $numberByCategories[$question->getCategoryTranslationKey()] = $numberByCategories[$question->getCategoryTranslationKey()] + 1;
            } else {
                $numberByCategories[$question->getCategoryTranslationKey()] = 1;
            }
        }

        $i = 1;
        $categories = [];
        foreach($numberByCategories as $category => $categoryNumber) {
            $categories[$i]['label'] = $category;
            $categories[$i]['percent'] = $categoryNumber;
            $i++;
        }

        //create word
        foreach ($data as $key => $value) {
            $this->setValue(strtoupper($key), $translator->translate($value));
            if ($key == 'state') {
                $this->setValue('TYPE', $translator->translate($value));
            }
        }

        //image
        $container = new Container('diagnostic');
        $this->setImageValue('image7.png', $container->bar);
        $this->setImageValue('image5.png', $container->pie);
        $this->setImageValue('image8.png', $container->radar);

        //number of recommandations
        $nbRecommandations = 0;
        foreach ($results as $result) {
            if ($result['recommandation']) {
                $nbRecommandations++;
            }
        }

        $this->setValue('ORGANIZATION_INFORMATION', $information['organization']);
        $this->setValue('EVALUATION_SYNTHESYS', $information['synthesis']);

        //recommandations
        $this->cloneRow('RECOMM_NUM', $nbRecommandations);

        $i = 1;
        foreach ($results as $result) {
            if ($result['recommandation']) {
                $name = 'RECOMM_NUM#' . $i;
                $this->setValue($name, $i);
                $i++;
            }
        }

        $i = 1;
        foreach ($results as $result) {
            if ($result['recommandation']) {
                $name = 'RECOMM_TEXT#' . $i;
                $this->setValue($name, $result['recommandation']);
                $i++;
            }
        }

        $i = 1;
        foreach ($results as $questionId => $result) {
            if ($result['recommandation']) {
                $name = 'RECOMM_DOM#' . $i;
                $this->setValue($name, $translator->translate($categories[$questions[$questionId]->getCategoryId()]['label']));
                $i++;
            }
        }

        $i = 1;
        foreach ($results as $result) {
            if ($result['recommandation']) {
                $gravity = '';
                switch ($result['gravity']) {
                    case 1:
                        $gravity = $translator->translate('__low');
                        break;
                    case 2:
                        $gravity = $translator->translate('__medium');
                        break;
                    case 3:
                        $gravity = $translator->translate('__strong');
                        break;
                }
                $name = 'RECOMM_GRAV#' . $i;
                $this->setValue($name, $gravity);
                $i++;
            }
        }

        $i = 1;
        foreach ($results as $result) {
            if ($result['recommandation']) {
                $maturity = $translator->translate('__maturity_none');
                switch ($result['maturity']) {
                    case 1:
                        $maturity = $translator->translate('__maturity_plan');
                        break;
                    case 2:
                        $maturity = $translator->translate('__maturity_medium');
                        break;
                    case 3:
                        $maturity = $translator->translate('__maturity_ok');
                        break;
                }
                $name = 'RECOMM_CURR_MAT#' . $i;
                $this->setValue($name, $maturity);
                $i++;
            }
        }

        $i = 1;
        foreach ($results as $result) {
            if ($result['recommandation']) {
                $maturityTarget = $translator->translate('__maturity_none');
                switch ($result['maturityTarget']) {
                    case 1:
                        $maturityTarget = $translator->translate('__maturity_plan');
                        break;
                    case 2:
                        $maturityTarget = $translator->translate('__maturity_medium');
                        break;
                    case 3:
                        $maturityTarget = $translator->translate('__maturity_ok');
                        break;
                }
                $name = 'RECOMM_TARG_MAT#' . $i;
                $this->setValue($name, $maturityTarget);
                $i++;
            }
        }

        $j = 1;
        foreach ($categories as $categoryId => $category) {

            $nbCategoryResults = 0;
            foreach ($results as $questionId => $result) {
                if ($questions[$questionId]->getCategoryId() == $categoryId) {
                    $nbCategoryResults++;
                }
            }

            $this->setValue('PRISE_NOTE_CATEG_' . $j, $translator->translate($category['label']));
            $this->setValue('CATEG__PERCENT_' . $j, $category['percent'] . '%');

            $this->cloneRow('PRISE_NOTE_TO_COLLECT_' . $j, $nbCategoryResults);

            $prise1 = 1;
            foreach ($results as $questionId => $result) {
                if ($questions[$questionId]->getCategoryId() == $categoryId) {
                    $name = 'PRISE_NOTE_TO_COLLECT_' . $j . '#' . $prise1;
                    $this->setValue($name, $translator->translate($questions[$questionId]->getTranslationKey()));
                    $prise1++;
                }
            }

            $prise2 = 1;
            foreach ($results as $questionId => $result) {
                if ($questions[$questionId]->getCategoryId() == $categoryId) {
                    $name = 'PRISE_NOTE_COLLECT_' . $j . '#' . $prise2;
                    $this->setValue($name, $result['notes']);
                    $prise2++;
                }
            }

            $prise3 = 1;
            foreach ($results as $questionId => $result) {
                if ($questions[$questionId]->getCategoryId() == $categoryId) {
                    $name = 'PRISE_NOTE_QUEST_' . $j . '#' . $prise3;
                    $prise3++;

                    if ($questions[$questionId]->getTranslationKeyHelp()) {
                        $this->setValue($name, strip_tags($translator->translate($questions[$questionId]->getTranslationKeyHelp())));
                    }
                }
            }

            $prise4 = 1;
            foreach ($results as $questionId => $result) {
                if ($questions[$questionId]->getCategoryId() == $categoryId) {
                    $name = 'PRISE_NOTE_1_' . $j . '#' . $prise4;
                    $value = ($result['maturity'] == 3) ? 'X' : '';
                    $this->setValue($name, $value);
                    $prise4++;
                }
            }

            $prise5 = 1;
            foreach ($results as $questionId => $result) {
                if ($questions[$questionId]->getCategoryId() == $categoryId) {
                    $name = 'PRISE_NOTE_2_' . $j . '#' . $prise5;
                    $value = ($result['maturity'] == 2) ? 'X' : '';
                    $this->setValue($name, $value);
                    $prise5++;
                }
            }

            $prise6 = 1;
            foreach ($results as $questionId => $result) {
                if ($questions[$questionId]->getCategoryId() == $categoryId) {
                    $name = 'PRISE_NOTE_3_' . $j . '#' . $prise6;
                    $value = ($result['maturity'] == 1) ? 'X' : '';
                    $this->setValue($name, $value);
                    $prise6++;
                }
            }

            $prise7 = 1;
            foreach ($results as $questionId => $result) {
                if ($questions[$questionId]->getCategoryId() == $categoryId) {
                    $name = 'PRISE_NOTE_4_' . $j . '#' . $prise7;
                    $value = ($result['maturity'] == 0) ? 'X' : '';
                    $this->setValue($name, $value);
                    $prise7++;
                }
            }

            $prise8 = 1;
            foreach ($results as $questionId => $result) {
                if ($questions[$questionId]->getCategoryId() == $categoryId) {
                    $name = 'PRISE_NOTE_RECOMM_' . $j . '#' . $prise8;
                    $this->setValue($name, $result['recommandation']);
                    $prise8++;
                }
            }

            $prise9 = 1;
            foreach ($results as $questionId => $result) {
                if ($questions[$questionId]->getCategoryId() == $categoryId) {
                    $name = 'PRISE_NOTE_TARG_1_' . $j . '#' . $prise9;
                    $value = ($result['maturityTarget'] == 3) ? 'X' : '';
                    $this->setValue($name, $value);
                    $prise9++;
                }
            }

            $prise10 = 1;
            foreach ($results as $questionId => $result) {
                if ($questions[$questionId]->getCategoryId() == $categoryId) {
                    $name = 'PRISE_NOTE_TARG_2_' . $j . '#' . $prise10;
                    $value = ($result['maturityTarget'] == 2) ? 'X' : '';
                    $this->setValue($name, $value);
                    $prise10++;
                }
            }

            $j++;
        }

        $this->saveAs($filepath);

        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-Length: " . filesize("$filepath") . ";");
        header("Content-Disposition: attachment; filename=$filename");
        header("Content-Type: application/octet-stream; ");
        header("Content-Transfer-Encoding: binary");

        readfile($filepath);
    }
}