<?php

namespace Drupal\dico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\NodeType;

class DataDictionnaryController extends ControllerBase {
	public function datadictionnary(){
        
        $output = [];
        $node_types = NodeType::loadMultiple();
  
        foreach($node_types as $bundle=>$bundle_def){		  
          $groups = [];
          $paragraphs = [];
          $groups = $this->field_list('node', $bundle, 2);
          //dpm($groups);
          
              
              $output[$bundle]['title'] = [
                  '#type'       => 'html_tag',
                  '#tag' => 'h1',
                  "#value"=>ucfirst($bundle_def->label()) .'[node:'.$bundle.']',
              ];
              if(isset($groups['paragraphs'])){
                  $paragraphs = $groups['paragraphs'];
                  unset($groups['paragraphs']);
              }
              $output[$bundle]['content'] = [
                  '#type'       => 'html_tag',
                  '#tag' => 'div',
                  'content'=>$groups
              ]; 
              if(!empty($paragraphs)){
                  foreach($paragraphs as $bundle=>$paragraph){
                      $output[$bundle]['title'] = [
                          '#type'       => 'html_tag',
                          '#tag' => 'h1',
                          "#value"=>ucfirst($paragraph['label']) .'[paragraph:'.$bundle.']',
                      ];
                      $output[$bundle]['content'] = [
                          '#type'       => 'html_tag',
                          '#tag' => 'div',
                          "content"=>$paragraph['content'],
                      ];
                  }
              }
        }
      //field_group_info_groups($entity_type, $bundle, $context, $mode);
       return array(
        '#theme' => 'markup',
        '#markup' =>\Drupal::service('renderer')->render($output),
      );
          
          
          
      }
  

	private function field_list(string $entity_type, string $bundle, int $level=2):array{
		$field_list = [];
		$paragraphs = [];
		//$name = 'field_implementation_progress';
		$display =\Drupal::entityTypeManager()
		  ->getStorage('entity_view_display')
		  ->load($entity_type.'.'.$bundle.'.default');
        
        //Quit the function if there is no display        
        if(is_null($display)) return [];
        
        //Todo get list of available laguage and loop on it
        $languages = \Drupal::languageManager()->getLanguages();
        if(isset($languages['en'])){
            $lang_code = 'en';
            $language = $languages['en'];
            unset($languages['en']);
            $langcodesList = array_keys($languages);
        }else{
            $langcodesList = array_keys($languages);
            $lang_code = $langcodesList[0];
            $language = $languages[$lang_code];
            unset($languages[$lang_code]);
            unset($langcodesList[0]);
        }
		\Drupal::languageManager()->setConfigOverrideLanguage($language);
		foreach($display->getComponents() as $field=>$field_settings){
			$field_instance = \Drupal\field\Entity\FieldConfig::loadByName($entity_type, $bundle, $field);
			  //
			  //===========================If the field instance exist and his status is true
            if($field_instance && $field_instance->get('status')){ //
				$field_list[$field] = [
					'content_type'=>$bundle,
					'field'=>$field,
					'type'=>$field_instance->getType(),
					'label_'.$lang_code=>$field_instance->getLabel(),
					'description_'.$lang_code=>$field_instance->getDescription()
                ];
                
                //Loop on other languages
                if(!empty($langcodesList)){
                    foreach($langcodesList as $k=>$v){
                        $field_list[$field]['label_'.$v]=$field_instance->getLabel();
                        $field_list[$field]['description_'.$v]=$field_instance->getDescription();
                    }
                }
                
                //Add settings
                $field_list[$field]['settings']= [
                    'data'=>[
						[
							'#type'       => 'html_tag',
							'#tag' => 'div',
							'#value'=>'Required : '.($field_instance->isRequired()?'Yes':'No')
						],
						[
							'#type'       => 'html_tag',
							'#tag' => 'div',
							'#value'=>'Multiple : '.($field_instance->isList()?'Yes':'No')						
						]
                    ]
				];
				if($field_instance->getType()=='entity_reference_revisions'){
                    //Get definition of the paragraph
                    $settings = $field_instance->getSettings();
                    $field_list[$field]['settings']['data'][] = [
                        '#type'       => 'html_tag',
                        '#tag' => 'div',
                        '#value'=>$settings['target_type'].' : '.implode(', ', (array)$settings['handler_settings']['target_bundles'])
                    ];
                    //TODO get description of the paragraph
                    foreach($settings['handler_settings']['target_bundles'] as $par_bundle){
                        $paragraphs[$par_bundle]  = [
                            'type'=>$par_bundle,
                            'label'=>$par_bundle,
                            'content'=>$this->field_list('paragraph', $par_bundle, $level--)
                        ];
                        /*
                        $field_list[$par_bundle]['settings']['data'][] = [
                        '#type'       => 'html_tag',
                        '#tag' => 'div',
                        '#value'=>$settings['target_type'].' : '.implode(', ', (array)$settings['handler_settings']['target_bundles'])
                    ];*/
                    }
				}
				elseif($field_instance->getType()=='tablefield'){
                    //Get cols names
                    //TODO
                    $DefaultValue = $field_instance->getDefaultValueLiteral();
                    if(isset($DefaultValue[0])){
                        $field_list[$field]['settings']['data'][] = [
                            '#type'       => 'html_tag',
                            '#tag' => 'div',
                            '#value'=>'<u>Columns</u>: <br/>'.implode(', <br/>', $DefaultValue[0]['value'][0])
                        ];
                    }
                    //break;
				}
				elseif($field_instance->getType()=='entity_reference'){
                    //Get the type of linked entity
                    $settings = $field_instance->getSettings();
                    if(isset($settings['handler_settings']['target_bundles'])){
                        $field_list[$field]['settings']['data'][] = [
                            '#type'       => 'html_tag',
                            '#tag' => 'div',
                            '#value'=>$settings['target_type'].' : '.implode(', ', (array)$settings['handler_settings']['target_bundles'])
                        ];
                    }
                    elseif(isset($settings['handler_settings']['view'])){
                        //Case of view definition
                        $field_list[$field]['settings']['data'][] = [
                            '#type'       => 'html_tag',
                            '#tag' => 'div',
                            '#value'=>'view'.' : '.$settings['handler_settings']['view']['view_name'].'.'.$settings['handler_settings']['view']['display_name']
                        ];
                    }
				}
			}
			else{
				$field_list[$field] = [
					'content_type'=>$bundle,
					'field'=>$field,
					'type'=>'',
					'label_'.$lang_code=>'',
					'description_'.$lang_code=>'',
                ];
				//Loop on other languages
                if(!empty($langcodesList)){
                    foreach($langcodesList as $k=>$v){
                        $field_list[$field]['label_'.$v]=$field_instance->getLabel();
                        $field_list[$field]['description_'.$v]=$field_instance->getDescription();
                    }
                }
				$field_list[$field]['settings']='';
				
			}
		}
		  
		//Loop on other languages
        if(!empty($langcodesList)){
            foreach($langcodesList as $k=>$v){
                $language = $languages[$v];
                \Drupal::languageManager()->setConfigOverrideLanguage($language);
                foreach($display->getComponents() as $field=>$field_settings){
                      $field_instance = \Drupal\field\Entity\FieldConfig::loadByName($entity_type, $bundle, $field);
                      //
                      //===========================If the field instance exist and his status is true
                      if($field_instance && $field_instance->get('status')){ 
                        if(isset($field_list[$field])){
                          $field_list[$field]['label_'.$v]=$field_instance->getLabel();
                          $field_list[$field]['description_'.$v]=$field_instance->getDescription();				
                        }
                      }
                }
            }
        }
		$groups = $display->getThirdPartySettings('field_group');
		  //
		  foreach($groups as $kg=>$vg){
			  $label = $vg['label'].' ['.$kg.']';
			  unset($groups[$kg]['label'],$groups[$kg]['parent_name'],$groups[$kg]['region'],$groups[$kg]['format_type'],$groups[$kg]['format_settings']);
			  if(empty($vg["children"])){
				 unset($groups[$kg]); 
			  }else{
				  $groups[$kg]['group_name'] = $kg;
				  $groups[$kg]['#type'] = 'container';
				   $groups[$kg]['#prefix'] = '<h'.$level.'>'.$label.'</h'.$level.'>';
				  //$groups[$kg]['#value'] =$label;
				  $groups[$kg]['content'] = [
						'#type'=>'table',
						'#header'=>[],
						'#rows'=>[]
					];
                  $headers = [];
				  foreach($vg["children"] as $kf=>$kv){
					  if(isset($field_list[$kv])){
						 $groups[$kg]['content']['#rows'][$kv]  = $field_list[$kv];
                         if(empty($headers)) $headers = array_keys($field_list[$kv]);
						 unset($field_list[$kv]);
					  }else{
						  $groups[$kg]['content']=[
							'#type'=>"container"
						  ];
						  $groups[$kg]['content'][$kv] = [];
					  }
				  }
                  $groups[$kg]['content']['#header'] = $headers;
				 
			  }
		  }
		  usort($groups, fn($a, $b) => $b['weight'] <=> $a['weight']);
		  $keys = array_column($groups, 'group_name');
		  $groups = array_combine($keys , $groups);
		  foreach($groups as $group=>$def){
			unset($groups[$group]['weight'],$groups[$group]['group_name'],$groups[$group]['children']);
			foreach($def['content'] as $kf=>$kv){
				if(isset($groups[$kf])){
					unset($groups[$kf]['weight'],$groups[$kf]['group_name'],$groups[$kf]['children']);
					$groups[$group]['content'][$kf] = $groups[$kf];
					unset($groups[$kf]);
				}
			}
		  }
		  if(!empty($field_list)){
			  unset($field_list['title'],$field_list['links'],$field_list['created'],$field_list['uid'],$field_list['metadata']);
			   if(!empty($field_list)){
				    $groups['fields'] = [
						'#type'=>'table',
						'#prefix'=>'<h'.$level.'>None grouped fields</h'.$level.'>',
						'#header'=>[],
						'#rows'=>[]
					];
                    $headers = [];
				    foreach($field_list as $field=>$field_def){
                       if(empty($headers)) $headers = array_keys($field_def);
					   $groups['fields']['#rows'][$field] = $field_def;
				    }
                    $groups['fields']['#header'] = $headers;
				   //dpm($field_list, $bundle);
			   }
		  }
		  $output = $groups;
		  if(!empty($paragraphs)) $output['paragraphs'] = $paragraphs;
		  return $output;
	}

  
}