<?php
//
// iTop module definition file
//

SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'schirrms-generic-connection-to-Datacenter-view/0.1.0',
	array(
		// Identification
		//
		'label' => 'Extensions for integrating Schirrms-Generic-Connection to Molkobain-Datacenter-View',
		'category' => 'business',

		// Setup
		//
		'dependencies' => array(
			'schirrms-generic-connection/0.7.2',
			//'molkobain-datacenter-view/1.5.1'
		),
		'mandatory' => false,
		'visible' => true,
		'auto_select' => 'SetupInfo::ModuleIsSelected("schirrms-generic-connection") && SetupInfo::ModuleIsSelected("molkobain-datacenter-view")',
		// Components
		//
		'datamodel' => array(
			'model.schirrms-generic-connection-to-datacenter-view.php'
		),
		'webservice' => array(
			
		),
		'data.struct' => array(
			// add your 'structure' definition XML files here,
		),
		'data.sample' => array(
			// add your sample data XML files here,
		),
		
		// Documentation
		//
		'doc.manual_setup' => '', // hyperlink to manual setup documentation, if any
		'doc.more_information' => '', // hyperlink to more information, if any 

		// Default settings
		//
		'settings' => array(
			// Module specific settings go here, if any
		),
	)
);


?>
