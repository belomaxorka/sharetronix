<?php if( !empty($D->uptodate) ){
		if( version_compare($_SESSION['INSTALL_DATA']['OLD_VERSION'], $C->VERSION, '>') ){
			echo $D->designer->errorMessage('Newer version installed', 'The database belongs to Sharetronix '.htmlspecialchars($_SESSION['INSTALL_DATA']['OLD_VERSION']).', which is newer than these files ('.$C->VERSION.'). Install the matching files or restore a backup.');
		}else{
			echo $D->designer->okMessage('Up to date', 'Your community already runs version '.$C->VERSION.'. There is nothing to update. <br /><br /> <a href="'.($_SESSION['INSTALL_DATA']['SITE_URL']).'"> Proceed to home page</a>' );
		}
	}elseif($D->errtype === 1){ ?>
	<div class="greygrad" style="margin-top: 5px;">
		<div class="greygrad2">
			<div class="greygrad3" style="padding-top:0px;">
				<p><strong>The following files and folders must have read and write permissions (CHMOD <b>0777</b> or <b>0766</b>)</strong></p>
				<table cellpadding="5" style="width:100%;">
				
				<?php foreach($D->files as $fl) { ?>
					<tr>
						<td colspan="2" style="font-size:0; line-height:0; height: 0; padding: 0; border-bottom: 1px solid #efefef;"></td>
					</tr>
					<tr>
						<td><?= $fl ?></td>
					</tr>
				<?php } ?>
				
					<tr>
						<td colspan="2" style="font-size:0; line-height:0; height: 0; padding: 0; border-bottom: 1px solid #efefef;"></td>
					</tr>
				</table>
			</div>
		</div>
	</div>
<?php 

	}elseif( $D->error ){ 
		echo $D->designer->errorMessage('Error', $D->errmsg);
 	}else{
		echo $D->designer->okMessage('Done', 'Your community has been updated to the latest version('.$C->VERSION.') <br /><br /> <a href="'.($_SESSION['INSTALL_DATA']['SITE_URL']).'"> Proceed to home page</a>' );
	}
 	
?>