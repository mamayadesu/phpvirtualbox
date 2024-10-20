<?php
require_once(dirname(__FILE__).'/../endpoints/lib/config.php');
require_once(dirname(__FILE__).'/../endpoints/lib/utils.php');
require_once(dirname(__FILE__).'/../endpoints/lib/vboxconnector.php');

// Init session
global $_SESSION;
session_init(true);

?>
<!--

	Shows guest networking adapters. This requires that Guest Additions be
	installed in the Guest VM

	Copyright (C) 2010-2015 Ian Moore (imoore76 at yahoo dot com)

	$Id: guestNetAdapters.html 595 2015-04-17 09:50:36Z imoore76 $ 
	
-->
<div id='vboxGuestNetAdapters'>
	
</div>
<!-- Used as a Template -->
<div id='vboxGuestNetAdapterTemplate' class='vboxDialogContent vboxTabContent' style='display: none'>
	<table class="vboxVertical">
		<tr>
			<th><span class='translate'>IPv4 Address</span>:</th>
			<td><span title='/V4/IP'></span></td>
		</tr>
		<tr>
			<th><span class='translate'>IPv4 Network Mask</span>:</th>
			<td><span title='/V4/Netmask'></span></td>
		</tr>
		<tr class='vboxIPv6' style='display: none'>
			<th><span class='translate'>IPv6 Address</span>:</th>
			<td><span title='/V6/Ip'></span></td>
		</tr>
		<tr class='vboxIPv6' style='display: none'>
			<th><span class='translate'>IPv6 Network Mask Length</span>:</th>
			<td><span title='/V6/Netmask'></span></td>
		</tr>
		<tr>
			<th><span class='vboxGuestMac'>MAC Address:</span></th>
			<td><span title='/MAC'></span></td>
		</tr>		
	</table>
</div>
	
<script type='text/javascript'>

/* Translate */
// Mac is special
$('#vboxGuestNetAdapterTemplate').find('span.vboxGuestMac').html(trans('Mac Address:','UIMachineSettingsNetwork'));

$('#vboxGuestNetAdapterTemplate').find(".translate").html(function(i,h){return trans(h,'UIGlobalSettingsNetwork');}).removeClass('translate');

/*
 * Get data and pass to init function
 */
function vboxVMNetAdaptersInit(vm) {
	
	// Add spinner and remove tab list if it exists
	$('#vboxGuestNetAdapterList').remove();
	$('#vboxGuestNetAdapters').prepend("<div class='vboxTabLoading'><img src='images/spinner.gif'></div>");
	
	var pattern = '/VirtualBox/GuestInfo/Net/*';
	$.when(vboxAjaxRequest('machineEnumerateGuestProperties',{'vm':vm,'pattern':pattern})).done(function(d){
		vboxVMNetAdaptersDisplay(d.responseData);
	});
}

function vboxVMNetAdaptersDisplay(d) {
	
	// Create hash / assoc array / mapping
	var data = {};
	for(var a = 0; a < d[0].length; a++) {
		data[d[0][a].replace('/VirtualBox/GuestInfo/Net/','')] = d[1][a];
	}

	// Remove spinner
	$('#vboxGuestNetAdapters').children().first().remove();

	// Append tab list
	var adl = $('<div />').attr({'id':'vboxGuestNetAdapterList','class':'vboxTabbed'});

	// Check for data
	if(!data['Count'] || data['Count'] < 1) {
		$(adl).html(trans('Unable to retrieve guest properties. Make sure the virtual machine is running and has the VirtualBox Guest Additions installed.','phpVirtualBox'));
		$('#vboxGuestNetAdapters').prepend(adl);
		return;
	}

	// Create list
	var ul = $('<ul />');
	
	// Each net adapter
	for(var i = 0; i < data['Count']; i++) {

		// Tab link
		$(ul).append($('<li />').html('<a href="#vboxGuestNetAdapter' + (i + 1) +'"><span>' + trans('Adapter %1','VBoxGlobal').replace('%1',(i + 1)) + '</span></a>'));

		// Tab content
		var tmpl = $("#vboxGuestNetAdapterTemplate").clone(true);
		tmpl.find('span[title]').each(function(){
			$(this).html(data[i+$(this).attr('title')]);
		});
		$(tmpl).attr('id','vboxGuestNetAdapter'+(i+1)).css({'display':''}).appendTo(adl);
		
	}

	// Tab links UL
	$(adl).prepend(ul);
	
	// prepend tabs
	$("#vboxGuestNetAdapters").prepend(adl);

	// Init display
	vboxInitDisplay('vboxGuestNetAdapters');

}

</script>
