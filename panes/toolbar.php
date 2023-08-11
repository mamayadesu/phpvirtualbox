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

	Top (main) phpVirtualBox tool bar
	Copyright (C) 2010-2015 Ian Moore (imoore76 at yahoo dot com)
	
	$Id: toolbar.html 599 2015-07-27 10:40:37Z imoore76 $

-->
<script type='text/javascript'>

/*
 * JS objects for actions that can be performed on a VM
 * from this toolbar
 */
var tButtons = [
    
    <?php if($is_admin):?>vboxVMActions['new'],<?endif;?>
    $.extend({},vboxVMActions['settings'],{click: function(){
        var vm = vboxChooser.getSingleSelected();
        if(!vm || vboxVMStates.isRunning(vm) || vboxVMStates.isPaused(vm)) return;
        vboxVMActions['settings'].click();
        
    }}),
    vboxVMActions['start'],
    {
        name: 'stop',
        icon: 'vm_poweroff',
        label: 'Stop',
        language_context: 'VBoxSelectorWnd',
        enabled: vboxVMActions['stop'].enabled,
        click : function() {return;}
    }
];

/* Append Top Toolbar */
var vboxChooserToolbarTop = new vboxToolbar({buttons: tButtons, size: 32,
    language_context: 'UIActionPool', renderTo: 'vboxPaneToolbar'});

/* Stop button menu and actions */
var stopMenuItems = [
    vboxVMActions['pause'],
    vboxVMActions['reset'],
];
for(var i = 0; i < vboxVMActions.stop_actions.length; i++) {
    var n = $.extend({}, vboxVMActions[vboxVMActions.stop_actions[i]]);
    if(i==0) n.separator = true;
    stopMenuItems[stopMenuItems.length] = n;
}
stopMenu = new vboxMenu({name: 'stopContextMenu', menuItems: stopMenuItems, language_context: 'UIActionPool'});

vboxChooserToolbarTop.getButtonElement('stop').contextMenu({
    menu: stopMenu.menuId(),
    button: 0,
    mode: 'menu'
    },function(a, el, pos) {
        for(var i in vboxVMActions) {
            if(typeof i == 'string' && vboxVMActions[i].name == a) {
                vboxVMActions[i].click();
                return;
            }
        }
    }
);

/* 'Settings' menu items / actions */
var vboxChooserSettingsMenu = {
		
	'settings' : {
		'label' : vboxVMActions['settings'].label,
		'icon' : vboxVMActions['settings'].icon
	},
	'storage' : {
		'icon' : 'hd'
	},
	'USB' : {
		'label' : 'USB',
		'icon' : 'usb'
	},
	'Network' : {
		'label' : 'Network',
		'icon' : 'nw'
	},
	'SharedFolders' : {
		'label' : 'Shared Folders',
		'icon' : 'sf'
	},
	'RemoteDisplay' : {
		'label' : 'Remote Display',
		'icon' : 'vrdp',
		'separator' : true
	},	
	'GuestAdditions' : {
		'label' : vboxVMActions['guestAdditionsInstall'].label,
		'icon' : vboxVMActions['guestAdditionsInstall'].icon,
		'separator' : true
	}

};


/* 'Settings' menu */
var ul = $('<ul />').attr({'class':'contextMenu','style':'display: none','id':'vboxVMSettingsMenu'});

for(var i in vboxChooserSettingsMenu) {
	
	// add name
	vboxChooserSettingsMenu[i].name = i;
    var label = trans(vboxChooserSettingsMenu[i].label, 'UIActionPool'); 
	var li = $('<li />').html("<span class='vboxMenuItemChecked' /><a href='#" + vboxChooserSettingsMenu[i].name + "' style='background-image: url(images/vbox/" + vboxChooserSettingsMenu[i].icon +"_16px.png);' >"+(label ? label : ' ')+"</a>");
	if(i == 'storage') { $(li).attr({'style':'display:none','id':'vboxVMSettingsToolbarStorageHolder'}); }
	if(i == 'USB') {
		/* 'USB' menu */
		$('<ul />').attr({'class':'vboxSettingsUSBAttachmentsMenu contextMenuNoBG','style':'display: none','id':'vboxVMSettingsUSBMenu'}).data({'callback':'vboxChooserToolbarUSBUpdate'}).appendTo(li);		
	}
	if(vboxChooserSettingsMenu[i].separator) $(li).addClass('separator');
	$(ul).append(li);
	
}

/* Append 'Settings' button Menu */
$('#vboxPane').append(ul);



/* 'Settings' button menu initialization  */
vboxChooserToolbarTop.getButtonElement('settings').contextMenu({
		menu: 'vboxVMSettingsMenu',
		button: 0,
		mode: 'menu'
	},
	function(a, el, pos, srcEl) {
		
		var vm = vboxChooser.getSingleSelected();
		
		if(!vm) return;
		
		switch(a) {
		
		    case 'Network':
		    case 'SharedFolders':
		        vboxVMsettingsDialog(vm, a);
		        break;

			case 'GuestAdditions':
				
				vboxVMActions['guestAdditionsInstall'].click();				
				break;
				
			// Don't do anything for dvd, fd, or USB devices main menu item click
			case 'dvdDevices':
			case 'fdDevices':
			case 'USB':
				break;
				
			case 'RemoteDisplay':
				var en = vboxVMDataMediator.getVMRuntimeData(vm.id).VRDEServer.enabled;
				vboxAjaxRequest('consoleVRDEServerSave',{'vm':vm.id,'enabled':(en ? 0 : 1)});
				break;

			case 'settings':
				vboxVMActions['settings'].click();
				break;
			
			default:
				
				// Assume it was a storage action
				if(vboxToolbarMediaLast) {
					if(vboxToolbarMediaLast.type == 'DVD') {
						vboxToolbarMediaMenuDVD.menuCallback(a,el,pos);
					} else {
						vboxToolbarMediaMenuFD.menuCallback(a,el,pos);
					}
				}
			
		} // </ switch / case >
		
	}
);

/*
 * Storage mount menu
 */
var vboxToolbarMediaLast = null; // Used when context menu item is clicked to determine
								// which medium attachment to act upon.
function vboxChooserToolbarMediumMount(medium) {
	
	var vmid = vboxChooser.getSingleSelectedId();
	
	var args = {'vm':vmid,'medium':medium,'port':vboxToolbarMediaLast.port,'device':vboxToolbarMediaLast.device,'bus':vboxToolbarMediaLast.bus,'controller':vboxToolbarMediaLast.controller};
	
	// Ajax request to mount medium
	var mount = new vboxLoader('mediumMount');
	mount.add('mediumMount',function(d){
		var l = new vboxLoader('getMedia');
		l.add('vboxGetMedia',function(d){$('#vboxPane').data('vboxMedia',d.responseData);});
		l.run();		
	},args);
	mount.run();	
}
var vboxToolbarMediaMenuDVD = new vboxMediaMenu('DVD',vboxChooserToolbarMediumMount,''); 
var vboxToolbarMediaMenuFD = new vboxMediaMenu('Floppy',vboxChooserToolbarMediumMount,'');

function vboxChooserToolbarStorageUpdate(menu) {

	vboxToolbarMediaLast = $(menu).data('storage');
	var medium = null;
	if(vboxToolbarMediaLast && vboxToolbarMediaLast.medium && vboxToolbarMediaLast.medium.id)
		medium = vboxMedia.getMediumById(vboxToolbarMediaLast.medium.id);

	if(vboxToolbarMediaLast.type == 'DVD') {
		vboxToolbarMediaMenuDVD.menuUpdateMedia(medium);
		return vboxToolbarMediaMenuDVD.menuElement();
	}
	
	vboxToolbarMediaMenuFD.menuUpdateMedia(medium);
	return vboxToolbarMediaMenuFD.menuElement();
}

// Update host drives and recent media on host change.
// Just recreate the menus
$('#vboxPane').on('hostChanged',function(){
	vboxToolbarMediaMenuDVD = new vboxMediaMenu('DVD',vboxChooserToolbarMediumMount,''); 
	vboxToolbarMediaMenuFD = new vboxMediaMenu('Floppy',vboxChooserToolbarMediumMount,'');	
});
/*
 * Update USB device list
 */
function vboxChooserToolbarUSBUpdate(menu) {
	
	$(menu).append($('<li />').html('<span><img src="images/jqueryFileTree/spinner.gif" /></span>').css({'width':'100px','text-align':'center'}));
	
	var vm = vboxChooser.getSingleSelected();
	
	var l = new vboxLoader();
	
	l.add('hostGetUSBDevices',function(d){
		$('#vboxPaneToolbar').data('hostUSB',d.responseData);
	},{});
	
	l.add('consoleGetUSBDevices',function(d){
		$('#vboxPaneToolbar').data('guestUSB',d.responseData);
	},{'vm':vm.id});
	
	l.noLoadingScreen = true;
	l.onLoad = function() {
		
		var hostUSB = $('#vboxPaneToolbar').data('hostUSB');
		var guestUSB = $('#vboxPaneToolbar').data('guestUSB');
		$(menu).children().remove();
		
		for(var i = 0; i < hostUSB.length; i++) {

			var dname = '';
			if(!hostUSB[i].product) {
				dname = trans('Unknown device %1:%2','UIActionPool').replace('%1',hostUSB[i].vendorId).replace('%2',hostUSB[i].productId);
			} else {
				dname = hostUSB[i].manufacturer + ' ' + hostUSB[i].product;
			}
			dname += ' [' + hostUSB[i].revision + ']';
			var capt = (hostUSB[i].state == 'Captured' && guestUSB[hostUSB[i].id]);
			var avail = (capt || (hostUSB[i].state != 'NotSupported' && hostUSB[i].state != 'Unavailable' && hostUSB[i].state != 'Captured'));
			var cbox = $('<input />').attr({'type':'checkbox','class':'vboxCheckbox'}).prop({'checked':(capt ? true : false),'disabled':(avail ? false : true)}).on('click',function(e){
				e.stopPropagation();
				if($(this).prop('disabled')) return;
				
				var cbox = $(this);
				// Detach
				if($(this).data('capt')) {
					$.when(vboxAjaxRequest('consoleUSBDeviceDetach',{'vm':vm.id,'id':$(this).data('usbDevice')})).done(function(d){
						if(d.success) {
							cbox.prop('checked',false);
							cbox.data('capt',false);
						} else {
							cbox.prop('checked',true);
						}
					});
				// Attach
				} else {
					$.when(vboxAjaxRequest('consoleUSBDeviceAttach',{'vm':vm.id,'id':$(this).data('usbDevice')})).done(function(d){
						if(d.success) {
							cbox.prop('checked',true);
							cbox.data('capt',true);
						} else {
							cbox.prop('checked',false);
						}
					});
				}
			}).data({'usbDevice':hostUSB[i].id,'capt':capt});
			
			$(menu).append($('<li />').append(
					$('<span />').addClass('vboxMenuAcceptClick')
						.click(function(){if(!$(this).parent().hasClass('disabled')){$(this).children('input').click();}return false;})
						.append(cbox).append(' '+dname)).attr({'class':(avail ? '' : 'disabled')}));
		}
		
		// No devices?
		if(hostUSB.length == 0) {
			$(menu).append($('<li />').html('<span>'+$('<div />').text(trans('<no devices available>','VBoxUSBMenu')).html()+'</span>'));
		}
		$(menu).trigger('menuLoaded');
	};
	l.run();
}

/*
 *  
 * Update vboxSettingsMenu items. Called when 
 * vboxChooser selection changes $('#vboxPane').onvmSelectionListChanged
 *
 */
function vboxUpdateSettingsMenu(vm) {

	if(vboxVMStates.isRunning(vm) || vboxVMStates.isPaused(vm)) {
		vboxChooserToolbarTop.getButtonElement("settings").enableContextMenu();
	} else {
		vboxChooserToolbarTop.getButtonElement("settings").disableContextMenu();
		return;
	}
	// enable or disable USB
	var usbEnabled = false;
	if(vm['USBControllers'].length) {
	    for(var i = 0; i < vm['USBControllers'].length; i++) {
	        if(vm['USBControllers'][i].type == 'OHCI') {
	            usbEnabled = true;
	            break;
	        }
	    }
	}
	if(usbEnabled) {
		$('ul.vboxSettingsUSBAttachmentsMenu').children().remove();
		$('#vboxVMSettingsMenu').find('a[href=#USB]').closest('li').css('display','');
	} else {
		$('#vboxVMSettingsMenu').find('a[href=#USB]').closest('li').css('display','none');
	}
	
	// Enable or disable network
	var enabledS = false;
	if(vm && vm.networkAdapters && vm.networkAdapters.length) {
		for(var a = 0; a < vm.networkAdapters.length; a++) {
			if(vm.networkAdapters[a].enabled) {
				enabledS = true;
				break;
			}
		}
	}
	if(enabledS) {
		$('#vboxVMSettingsMenu').find('a[href=#Network]').closest('li').css('display','');
	} else {
		$('#vboxVMSettingsMenu').find('a[href=#Network]').closest('li').css('display','none');
	}
	
	// vboxVMSettingsToolbarStorageHolder
	var smenu = $('#vboxVMSettingsToolbarStorageHolder');
	smenu.siblings('li.vboxVMSettingsStorage').remove();
	$('ul.vboxVMSettingsStorage').remove();
	var enabledS = false;
	if(vm && vm.storageControllers && vm.storageControllers.length) {
		var dvdDevices = new Array();
		var fdDevices = new Array();
		
		for(var a = 0; a < vm.storageControllers.length; a++) {
			
			// See if this bus type supports removable media
			if(jQuery.inArray('dvd',vboxStorage[vm['storageControllers'][a].bus].driveTypes) == -1 &&
					jQuery.inArray('floppy',vboxStorage[vm['storageControllers'][a].bus].driveTypes) == -1)
				continue;
			
			var icon = vboxStorage.getBusIconName(vm['storageControllers'][a].bus);
			
			for(var b = 0; b < vm['storageControllers'][a]['mediumAttachments'].length; b++) {
				
				if(vm['storageControllers'][a]['mediumAttachments'][b].type == 'HardDisk') continue;
				
				vm['storageControllers'][a]['mediumAttachments'][b]['controller'] = vm['storageControllers'][a]['name'];
				
				var portName = vboxStorage[vm['storageControllers'][a].bus].slotName(vm['storageControllers'][a]['mediumAttachments'][b].port, vm['storageControllers'][a]['mediumAttachments'][b].device);
				var m = vm['storageControllers'][a]['mediumAttachments'][b].medium;
				m = vboxMedia.getMediumById((m && m.id ? m.id : null));
				
				var mName = vboxMedia.getName(m);
				mName = $('<div />').text(mName).html();
				
				
				var smid = vm.id+'-vboxVMSettingsStorage-'+a+'-'+b;
				$('#'+vm.id+'-vboxVMSettingsStorage-'+a+'-'+b).remove();

				var li = $('<li />').attr({'title':mName}).html("<a title='"+mName+"' href='#mount-"+vm['storageControllers'][a].bus+"-"+vm['storageControllers'][a]['mediumAttachments'][b].port+"-"+vm['storageControllers'][a]['mediumAttachments'][b].device+"' style='background-image:url(images/vbox/"+icon+"_16px.png);'>"+vm['storageControllers'][a]['name'] + ' ('+portName + ")</a>").addClass('vboxVMSettingsStorage');
				$(li).append($('<ul />').attr({'id':smid,'style':'display:none'}).data({'callback':'vboxChooserToolbarStorageUpdate','storage':vm['storageControllers'][a]['mediumAttachments'][b]}));
				if(vm['storageControllers'][a]['mediumAttachments'][b].type == 'DVD') {
					dvdDevices[dvdDevices.length] = li;
				} else {
					fdDevices[fdDevices.length] = li;
				}
				enabledS = true;
			}
		}
		if(dvdDevices.length) {
			var ul = null;
			var li = $('<li />').html("<a href='#dvdDevices' style='background-image:url(images/vbox/cd_16px.png);'>"+trans('Optical Drives','UIActionPool')+'</a>').addClass('vboxVMSettingsStorage');
			if(dvdDevices.length == 1) {
				ul = dvdDevices[0].children('ul').first();
			} else {
				ul = $('<ul />').attr({'style':'display:none'}).addClass('vboxVMSettingsStorage');
				for(var i = 0; i < dvdDevices.length; i++) {
					$(ul).append(dvdDevices[i]);	
				}
			}
			$(li).append(ul).insertBefore(smenu);
		}
		
		if(fdDevices.length) {
			var ul = null;
			var li = $('<li />').html("<a href='#fdDevices' style='background-image:url(images/vbox/fd_16px.png);'>"+trans('Floppy Devices','UIActionPool')+'</a>').addClass('vboxVMSettingsStorage');
			if(fdDevices.length == 1) {
				ul = fdDevices[0].children('ul').first();
			} else {
				ul = $('<ul />').attr({'style':'display:none'}).addClass('vboxVMSettingsStorage');
				for(var i = 0; i < fdDevices.length; i++) {
					$(ul).append(fdDevices[i]);	
				}
			}
			$(li).append(ul).insertBefore(smenu);
		}
		
	}
	if(enabledS) {
		$('#vboxVMSettingsMenu').find('a[href=#Network]').parent().addClass('separator');
	} else {
		$('#vboxVMSettingsMenu').find('a[href=#Network]').parent().removeClass('separator');
	}
	
	// Enable remote display?
	if(vm && vm.VRDEServer) {
		$('#vboxVMSettingsMenu').find('a[href=#RemoteDisplay]').css({'background-image':'url(images/vbox/vrdp' + (vm.VRDEServer.enabled ? '_on' : '') + '_16px.png)'}).parent().removeClass('disabled')
		.addClass((vm.VRDEServer.enabled ? 'vboxMenuItemChecked' : '')).removeClass((vm.VRDEServer.enabled ? '' : 'vboxMenuItemChecked'));
	} else {
		$('#vboxVMSettingsMenu').find('a[href=#RemoteDisplay]').css({'background-image':'url(images/vbox/vrdp_disabled_16px.png)'}).parent().addClass('disabled');
	}	
	
	
}


/*
 Bind events...
 */

// Selection list changed
$('#vboxPane').on('vmSelectionListChanged',function(e) {
	
	vboxChooserToolbarTop.update(vboxChooser);
	vboxUpdateSettingsMenu();
	
	// Check for a single selected VM and
	// update settings menu based on its runtime data
	if(vboxChooser.selectedVMs.length == 1 && (vboxChooser.isSelectedInState('Running') || vboxChooser.isSelectedInState('Paused'))) {
		
		// Get data
		$.when(vboxVMDataMediator.getVMDataCombined(vboxChooser.selectedVMs[0]))
			.done(function(d) {
				
				vboxUpdateSettingsMenu(d);

			});
		
	}

// Update menus on these events
}).on('vboxEvents', function(e, eventList) {

	var updateToolbar = false;
	var updateSettingsMenu = false;
	var updateVRDE = false;
	
	for(var i = 0; i < eventList.length && !(updateToolbar && updateSettingsMenu); i++) {
	
		switch(eventList[i].eventType) {
		
			// Machine or session state change
			case 'OnMachineStateChanged':
			case 'OnSessionStateChanged':
				if(vboxChooser.isVMSelected(eventList[i].machineId)) {
					updateToolbar = true;
				}
				if(vboxChooser.getSingleSelectedId() == eventList[i].machineId) {
					updateSettingsMenu = true;
				} 
				break;
				
			// Machine or medium data change
			case 'OnMachineDataChanged':
			case 'OnMediumChanged':
				if(vboxChooser.isVMSelected(eventList[i].machineId)) {
					updateToolbar = true;
					updateSettingsMenu = true;
				}
				break;
			
			// VRDE Server
			case 'OnVRDEServerChanged':
			case 'OnVRDEServerInfoChanged':
				if(!updateSettingsMenu && !updateVRDE && vboxChooser.isVMSelected(eventList[i].machineId)) {
					if(eventList[i].enrichmentData && eventList[i].enrichmentData.enabled) {
						updateVRDE = function(){
							$('#vboxVMSettingsMenu').find('a[href=#RemoteDisplay]').css({'background-image':'url(images/vbox/vrdp_on_16px.png)'}).parent().removeClass('disabled')
								.addClass('vboxMenuItemChecked');
						};
					} else {
						updateVRDE = function(){
							$('#vboxVMSettingsMenu').find('a[href=#RemoteDisplay]').css({'background-image':'url(images/vbox/vrdp_16px.png)'}).parent().removeClass('disabled')
								.removeClass('vboxMenuItemChecked');
							
						};						
					}
				}
				break;
				
			
		} // </ switch event type >
		
	} // </ for each event >
	
	// Toolbar
	if(updateToolbar) {
		vboxChooserToolbarTop.update(vboxChooser);
	}
	
	// Entire settings menu or just VRDE
	if(updateSettingsMenu) {

		// Check for a single selected VM and
		// update settings menu based on its runtime data
		if(vboxChooser.selectedVMs.length == 1 && (vboxChooser.isSelectedInState('Running') || vboxChooser.isSelectedInState('Paused'))) {
			
			// Get data
			$.when(vboxVMDataMediator.getVMDataCombined(vboxChooser.selectedVMs[0]))
				.done(function(d) {
					vboxUpdateSettingsMenu(d);
				});
			
		} else {
			vboxUpdateSettingsMenu();
		}
		
	} else if(updateVRDE) {
		
		updateVRDE();
		
	}

});

$('#vboxVMSettingsMenu').disableContextMenu();


</script>
