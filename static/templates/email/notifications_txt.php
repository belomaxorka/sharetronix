<?= $D->page->lang('eml_ntf_title', array('#USER#'=>$D->user->username), isset($D->user->language) ? $D->user->language : FALSE) ?>


<?= $D->message_txt ?>


<?= $D->page->lang('emltxt_ntf_editnotif', array('#A0#'=>$C->SITE_URL.'settings/notifications', '#A1#'=>'<a href="'.$C->SITE_URL.'settings/notifications" target="_blank">', '#A2#'=>'</a>'), isset($D->user->language) ? $D->user->language : FALSE) ?>


<?= $D->page->lang('eml_ntf_signatr', array('#SITE_URL#'=>$C->OUTSIDE_SITE_URL), isset($D->user->language) ? $D->user->language : FALSE) ?>