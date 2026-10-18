<?php
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if(!$this->user->is_logged){
		$this->redirect('signin');
	}
	
	require_once( $C->INCPATH.'helpers/func_parse_forum_tags.php' );
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/faq.php');
	
	$num_faq_categories	= (int) $this->lang('faqpb_cats_number');
	$menu = array();
	$menu_items = array();
	$category = 1;
	$table = new tableCreator();
	$faq_content = array();
	
	if($this->param('category')){
		$param = (int) trim($this->param('category'));
		$category = ($param < 1 || $param > (int) $this->lang('faqpb_cats_number'))? 1 : $param;
	}

	//TEMPLATE CODE START
	$tpl = new template( array('page_title' => $this->lang('faq_page_title', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc faq-layout') );
	
	for($i=1; $i<=$num_faq_categories; $i++){
		$menu[] = array(	'url' => 'faq/category:'.$i, 		
							'css_class' => (($category == $i)? ' selected' : ''), 	
							'title' => $this->lang('faqpb_c'.$i.'_title')
				);
		
		$num_topics	= intval($this->lang('faqpb_c'.$i.'_posts_number'));
		if( $i == $category ){
			for($j=1; $j<=$num_topics; $j++){
				$image = $this->lang('faqpb_c'.$i.'_p'.$j.'_image');
				
				if(!empty($image)){
					$image = '<br/><img src="'.$C->SITE_URL.$this->lang('faqpb_c'.$i.'_p'.$j.'_image').'" style="max-width: 350px;padding-right:10px;"/>';
				}else{
					$image = '';
				}			
				
				$faq_content[] = $table->textField( 
						'<strong>'.$this->lang('faqpb_c'.$i.'_p'.$j.'_title', array('#SITE_TITLE#'=>$C->SITE_TITLE)).'</strong>'.$image, 
						nl2br(bb_apply_tags($this->lang('faqpb_c'.$i.'_p'.$j.'_text', 
						array('#SITE_URL#'=>$C->SITE_URL, '#SITE_TITLE#'=>$C->SITE_TITLE)))) );
			}
		}
	}
	
	$tpl->layout->setVar( 'left_content_placeholder', $tpl->designer->createInfoBlock($this->lang('faq_menu_title'), $tpl->designer->createMenu('feed-navigation', $menu, 'dashboard_main_left_menu')) );
	$tpl->layout->setVar('main_content', $table->createTableInput( $faq_content ). '<style>ul.feed-navigation li a.blog, ul.feed-navigation li a.post-reshare{ display:none; }</style>');
	
	
	$tpl->display();
	//TEMPLATE CODE END