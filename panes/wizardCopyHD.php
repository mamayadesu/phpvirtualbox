<?php
require_once(dirname(__FILE__).'/../endpoints/lib/config.php');
require_once(dirname(__FILE__).'/../endpoints/lib/utils.php');
require_once(dirname(__FILE__).'/../endpoints/lib/vboxconnector.php');

// Init session
global $_SESSION;
session_init(true);

if (!$_SESSION['admin']) {
    die("You don't have permissions");
}
?>
<!--

	Panes for copy hard disk wizard. Logic in vboxWizard()
	Copyright (C) 2010-2015 Ian Moore (imoore76 at yahoo dot com)
	
	$Id: wizardCopyHD.html 595 2015-04-17 09:50:36Z imoore76 $

 -->
<!-- Step 1 -->
<div id='wizardCopyHDStep1' title='Hard disk to copy' style='display: none'>
	
	<span class='translate'>&lt;p&gt;Please select the virtual hard disk file that you would like to copy if it is not already selected. You can either choose one from the list or use the folder icon beside the list to select one.&lt;/p&gt;</span>

	<div class='vboxOptions'>
		<table class='vboxOptions'>
			<tr style='vertical-align: top;'>
				<td><select id="copyHDDiskSelectId" name="copyHDDiskSelect" onchange='wizardCopyHDUpdateName(this)'></select></td>
				<td style='width:1%' id='newVMDiskVMM'></td>
			</tr>
		</table>
	</div>
</div>


<!-- Step 2 -->
<div id='wizardCopyHDStep2' title='Hard disk file type' style='display: none'>

	<p class='translate'>Please choose the type of file that you would like to use for the new virtual hard disk. If you do not need to use it with other virtualization software you can leave this setting unchanged.</p>

	<div class='vboxOptions'>
		<table class='vboxOptions'>
			<tr style='vertical-align: top;'>
				<td><label><input type='radio' class='vboxRadio' checked='checked' name='copyHDFileType' value='vdi' /> <span class='translate'>VDI (VirtualBox Disk Image)</span></label></td>
			</tr>
			<tr style='vertical-align: top;'>
				<td><label><input type='radio' class='vboxRadio' name='copyHDFileType' value='vmdk' /> <span class='translate'>VMDK (Virtual Machine Disk)</span></label></td>
			</tr>
			<tr style='vertical-align: top;'>
				<td><label><input type='radio' class='vboxRadio' name='copyHDFileType' value='vhd' /> <span class='translate'>VHD (Virtual Hard Disk)</span></label></td>
			</tr>
			
		</table>
	</div>
	
</div>


<!-- Step 3 -->
<div id='wizardCopyHDStep3' title='Storage on physical hard disk' style='display: none'>

	<p class='translate'>Please choose whether the new virtual hard disk file should grow as it is used (dynamically allocated) or if it should be created at its maximum size (fixed size).</p>
	
	<span class='translate vboxCreateDynamic'>&lt;p&gt;A &lt;b&gt;dynamically allocated&lt;/b&gt; hard disk file will only use space on your physical hard disk as it fills up (up to a maximum &lt;b&gt;fixed size&lt;/b&gt;), although it will not shrink again automatically when space on it is freed.&lt;/p&gt;</span>
	
	<span class='translate vboxCreateFixed'>&lt;p&gt;A &lt;b&gt;fixed size&lt;/b&gt; hard disk file may take longer to create on some systems but is often faster to use.&lt;/p&gt;</span>
	
	<span class='translate vboxCreateSplit2G'>&lt;p&gt;You can also choose to &lt;b&gt;split&lt;/b&gt; the hard disk file into several files of up to two gigabytes each. This is mainly useful if you wish to store the virtual machine on removable USB devices or old systems, some of which cannot handle very large files.</span>
	
	<div class='vboxOptions'>
		<table class='vboxOptions'>
			<tr style='vertical-align: top;' class='vboxCreateDynamic'>
				<td><label><input type='radio' class='vboxRadio' checked='checked' name='newHardDiskType' value='dynamic' /> <span class='translate'>Dynamically allocated</span></label></td>
			</tr>
			<tr style='vertical-align: top;' class='vboxCreateFixed'>
				<td><label><input type='radio' class='vboxRadio' name='newHardDiskType' value='fixed' /> <span class='translate'>Fixed size</span></label></td>
			</tr>
			<tr style='vertical-align: top;' class='vboxCreateSplit2G'>
				<td><label><input type='checkbox' class='vboxCheckbox' name='newHardDiskSplit' /> <span class='translate'>Split into files of less than 2GB</span></label></td>
			</tr>								
		</table>
	</div>
</div>

<!-- Step 4 -->
<div id='wizardCopyHDStep4' title='New hard disk to create' style='display: none'>

	<p class='translate'>Please type the name of the new virtual hard disk file into the box below or click on the folder icon to select a different folder to create the file in.</p>
	
	<div class='vboxOptions'>
		<table class='vboxOptions'>
			<tr>
				<td style='width: 100%; white-space: nowrap'>
					<input type='text' class='vboxText' name='wizardCopyHDLocation' style='width: 100%'/>
				</td>
				<td style='width: 1%;' id='newVMDiskVMMDest'></td>
			</tr>
		</table>
	</div>	

</div>

<script type='text/javascript'>


// Fill HD type options
var vboxHDTypesTbl = $('#wizardCopyHDStep2').find('table.vboxOptions').first();
vboxHDTypesTbl.children().remove();
var vboxHDTypes = $('#vboxPane').data('vboxSystemProperties').mediumFormats;
for(var i = 0; i < vboxHDTypes.length; i++) {
	if(jQuery.inArray('CreateFixed',vboxHDTypes[i].capabilities) < 0 && jQuery.inArray('CreateDynamic',vboxHDTypes[i].capabilities) < 0) continue;
	if(jQuery.inArray('HardDisk',vboxHDTypes[i].deviceTypes) > -1) {
		vboxHDTypesTbl.append("<tr style='vertical-align: top;'><td><label><input type='radio' class='vboxRadio' name='copyHDFileType' value='"+vboxHDTypes[i].id+"' /> "+vboxMedia.getFormat({'format':vboxHDTypes[i].name})+"</label></td></tr>");
		vboxHDTypesTbl.find('tr').last().data('vboxFormat', vboxHDTypes[i]);
	}
}
// Select default HD format and place it at the top
vboxHDTypesTbl.find('input[value='+$('#vboxPane').data('vboxSystemProperties').defaultHardDiskFormat+']').prop('checked',true).closest('tr').detach().prependTo(vboxHDTypesTbl);


/* Choose virtual hard disk button */
new vboxToolbarSingle({button: {
	'name' : 'mselecthdbtn',
	'label' : 'Choose a virtual hard disk file to copy...',
	'language_context': 'UIWizardCloneVD',
	'icon' : 'select_file',
	'click' : function () {
		vboxMedia.actions.choose(null,'HardDisk',function(med){
			if(med) copyHDFillDisks(med.base);
		});
	}
}}).renderTo('newVMDiskVMM');		

/* Choose location of new file button */
new vboxToolbarSingle({button: {
	'name' : 'mselecthdbtn',
	'label' : 'Choose a location for new virtual hard disk file...',
	'language_context': 'UIWizardNewVD',
	'icon' : 'select_file',
	'click' : function () {
		wizardCopyHDBrowseLocation();
	}
}}).renderTo('newVMDiskVMMDest');		

	
/* Set up disk selection box */
function copyHDFillDisks(sel) {

	document.forms['frmwizardCopyHD'].copyHDDiskSelect.options.length = 0;
	$(document.forms['frmwizardCopyHD'].copyHDDiskSelect).children().remove();
		
	var s = vboxMedia.mediaForAttachmentType('HardDisk');
	
	// Sort media
	s.sort(function(a,b){return strnatcasecmp(a.name,b.name);});
	
	var mediumSelects = [];
	for(var i = 0; i < s.length; i++) {
		document.forms['frmwizardCopyHD'].copyHDDiskSelect.options[i] = new Option(vboxMedia.mediumPrint(s[i]),s[i].id);
		if(s[i].readOnly && s[i].deviceType == 'HardDisk') $(document.forms['frmwizardCopyHD'].copyHDDiskSelect.options[i]).addClass('vboxMediumReadOnly');
		mediumSelects[i] = {'attachedId':s[i].id,'id':s[i].id,'base':s[i].base,'label':vboxMedia.mediumPrint(s[i])};
	}
	if(sel) {
		$(document.forms['frmwizardCopyHD'].copyHDDiskSelect).val(sel);
	}
	
	$(document.forms['frmwizardCopyHD'].copyHDDiskSelect).mediumselect({'type':'HardDisk','showdiff':false,'media':mediumSelects});

}
copyHDFillDisks();

/* Browse for new disk location */
function wizardCopyHDBrowseLocation() {

	// Get current location
	var loc = document.forms['frmwizardCopyHD'].elements.wizardCopyHDLocation.value;
	if(loc.indexOf(':') > 0) {
		// windows
		loc = loc.replace(/.*\\/,'');
	} else if(loc.indexOf('/') != -1) {
		// *nix
		loc = loc.replace(/.*\//,'');
	} else {
		// no path set, use src location
		loc = vboxDirname(vboxMedia.getMediumById($(document.forms['frmwizardCopyHD'].copyHDDiskSelect).val()).location);
	}
		
	vboxFileBrowser(loc,function(f){
		if(!f) return;
		// get file name
		file = document.forms['frmwizardCopyHD'].elements.wizardCopyHDLocation.value;
		document.forms['frmwizardCopyHD'].elements.wizardCopyHDLocation.value = f+$('#vboxPane').data('vboxConfig').DSEP+file;
	},true);

}

/* Update new HD name */
function wizardCopyHDUpdateName(sel) {
	var n = $(sel).val();
	var m = vboxMedia.getMediumById(n);
	if(!m) return;
	document.forms['frmwizardCopyHD'].elements.wizardCopyHDLocation.value = trans('%1_copy','UIWizardNewVD').replace('%1',m.name.replace(/\.[^\.]+?$/,''));
}

 /* Suggested Data exists */
$('#wizardCopyHDStep1').on('show',function(e,wiz){

	// Already initialized?
	if($('#wizardCopyHDStep1').data('init')) return;

    if(wiz && wiz.suggested && wiz.suggested.medium)
    	$(document.forms['frmwizardCopyHD'].copyHDDiskSelect).mediumselect({'selectMedium':wiz.suggested.medium});
    
    
	$('#wizardCopyHDStep1').data('init',true);
	
});

$('#wizardCopyHDStep2').on('show',function(e,wiz){
	wiz._lastStep = 2;
});
$('#wizardCopyHDStep4').on('show',function(e,wiz){
	wiz._lastStep = 4;
});

/* WHen showing step 3, show / hide split option */
$('#wizardCopyHDStep3').on('show',function(e,wiz){
		
	var caps = new Array();
	for(var i = 0; i < document.forms['frmwizardCopyHD'].copyHDFileType.length; i++) {
		if(document.forms['frmwizardCopyHD'].copyHDFileType[i].checked) {
			caps = $(document.forms['frmwizardCopyHD'].copyHDFileType[i]).closest('tr').data('vboxFormat').capabilities;
			break;
		}
	}
	
	var capOpts = ['CreateFixed','CreateDynamic','CreateSplit2G'];
	for(var i = 0; i < capOpts.length; i++) {
		if(jQuery.inArray(capOpts[i],caps) < 0) {
			$('#wizardCopyHDStep3').find('.vbox'+capOpts[i]).hide();
		} else {
			$('#wizardCopyHDStep3').find('.vbox'+capOpts[i]).show();
		}
	}
	
	// Select first visible option
	$('#wizardCopyHDStep3').find('tr:visible').first().find('input').prop('checked',true);
	
	if($('#wizardCopyHDStep3').find('tr:visible').length == 1) {
		if(wiz._lastStep == 2) wiz.displayStep(4);
		else if(wiz._lastStep == 4) wiz.displayStep(2);
	}
});

 
</script>
