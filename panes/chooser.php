<?php

require_once(dirname(__FILE__).'/../endpoints/lib/config.php');
require_once(dirname(__FILE__).'/../endpoints/lib/utils.php');
require_once(dirname(__FILE__).'/../endpoints/lib/vboxconnector.php');

// Init session
global $_SESSION;
session_init(true);

$is_admin = !!$_SESSION['admin'];
?>
<!--

	Virtual Machine List
	Copyright (C) 2010-2015 Ian Moore (imoore76 at yahoo dot com)
	
	$Id: chooser.html 595 2015-04-17 09:50:36Z imoore76 $
	
	@see js/chooser.js

 -->
<div id='vboxChooserDiv'></div>
<script type='text/javascript'>


/*
 * 
 *
 * Startup for VM List
 *
 */

vboxChooser.setAnchorId('vboxChooserDiv');

// Stop actions
var sChildren = [];
for(var i = 0; i < vboxVMActions.stop_actions.length; i++) {
	sChildren[sChildren.length] = $.extend(true,{},vboxVMActions[vboxVMActions.stop_actions[i]],{'iconStringDisabled':'_disabled'});
}

// VM List Group context menu
vboxChooser.setContextMenu('group', [
	<?php if($is_admin):?>vboxVMGroupActions['newmachine'],
	vboxVMGroupActions['addmachine'],
	$.extend({}, vboxVMGroupActions['rename'], {separator: true}),
	vboxVMGroupActions['ungroup'],<?php endif;?>
	$.extend({}, vboxVMActions['start'], {'separator' : true}),
    vboxVMActions['pause'],
    vboxVMActions['reset'],
	$.extend({},vboxVMActions['stop'], {'children':sChildren}),
	$.extend({},vboxVMActions['discard'], {separator: true}),
	vboxVMActions['refresh'],
	$.extend({}, vboxVMGroupActions['sort'], {separator:true}),
]);


/*
 * VM Context menu setup (menu per VM in list)
 */
 
vboxChooser.setContextMenu('vm',[
    <?php if($is_admin):?>vboxVMActions['settings'],
   	vboxVMActions['clone'],
   	vboxVMActions['remove'],
   	vboxVMActions['group'],<?php endif;?>
   	$.extend({},vboxVMActions['start'], {'separator' : true}),
    vboxVMActions['pause'],
    vboxVMActions['reset'],
   	$.extend({},vboxVMActions['stop'], {'children': sChildren}),
   	$.extend({},vboxVMActions['discard'], {'separator' : true}),
   	vboxVMActions['logs'],
   	vboxVMActions['refresh']
]);

// Don't need thse anymore
sChildren = null;

/*
 * VM list context menu setup
 */
vboxChooser.setContextMenu('anchor', [
	<?php if($is_admin):?>vboxVMActions['new'],
	vboxVMActions['add'],
	{
		'name':'fileImport',
		'label':'Import Appliance...',
		'icon':'import',
		'click':function(){
			new vboxWizardImportApplianceDialog().run();
		},
		'separator': true
	},
	{
		'name':'fileExport',
		'label':'Export Appliance...',
		'icon':'export',
		'click':function(){new vboxWizardExportApplianceDialog().run(); }
	},
    <?php endif;?>
	$.extend({},vboxVMGroupActions['sort'],{'separator':true,click:function(){
		vboxChooser.sortSelectedGroup(true);
	}})
]);


vboxChooser.start();

</script>
