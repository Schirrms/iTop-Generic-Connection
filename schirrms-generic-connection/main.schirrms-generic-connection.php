<?php

// Copyright (C) 2018 Combodo SARL
//
//   This file is part of an iTop extension.
//
//   iTop is free software; you can redistribute it and/or modify
//   it under the terms of the GNU Affero General Public License as published by
//   the Free Software Foundation, either version 3 of the License, or
//   (at your option) any later version.
//
//   iTop is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU Affero General Public License for more details.
//
//   You should have received a copy of the GNU Affero General Public License
//   along with iTop. If not, see <http://www.gnu.org/licenses/>

/**
 * main.schirrms-comm-interface.php
 * 
 * @author Pascal Schirrmann <schirrms@schirrms.net>
 */

// Attempt to create a trigerred event class, instead of using the Method AfterInsert, AfterUpdate and AfterDelete
// see the thread : https://sourceforge.net/p/itop/discussion/922360/thread/e31171e91f/
class GenericCommTriggers implements iApplicationObjectExtension
{
	public function OnIsModified($oObject)
	{
		return false;
	}
	public function OnCheckToWrite($oObject)
	{
		return array();
	}
	public function OnCheckToDelete($oObject)
	{
		return array();
	}
	public function OnDBUpdate($oObject, $oChange = null)
	{
		$sDebugFile=$_SERVER['CONTEXT_DOCUMENT_ROOT']."/debug/dd-".date("Y-m-d").".txt";
		file_put_contents($sDebugFile, "BEGIN : ".date("H:i:s")."\n", FILE_APPEND);
		file_put_contents($sDebugFile, "In the GenericCommTrigger Class for the device ".$oObject->name."\n", FILE_APPEND);
		file_put_contents($sDebugFile, "Object Class ".$oObject->finalclass."\n", FILE_APPEND);
		// only for Generic interfaces
		if(($oObject instanceof GenericCommInterface) === false) { return; }
		file_put_contents($sDebugFile, "Instance is OK, continue...\n", FILE_APPEND);
		file_put_contents($sDebugFile, "print_r $oObject\n", FILE_APPEND);
		file_put_contents($sDebugFile, print_r($oObject, true), FILE_APPEND);
		file_put_contents($sDebugFile, "print_r $oObject->Get()\n", FILE_APPEND);
		file_put_contents($sDebugFile, print_r($oObject->Get(), true), FILE_APPEND);
		file_put_contents($sDebugFile, "print_r $oObject->GetOriginal()\n", FILE_APPEND);
		file_put_contents($sDebugFile, print_r($oObject->GetOriginal(), true), FILE_APPEND);
		file_put_contents($sDebugFile, "print_r $oObject->ListChanges()\n", FILE_APPEND);
		file_put_contents($sDebugFile, print_r($oObject->ListChanges(), true), FILE_APPEND);

		if (isset($oObject->m_aCurrValues->connectableci_id))
		{
			file_put_contents($sDebugFile, "Value of \$oObject->m_aCurrValues->connectableci_id : '".$oObject->m_aCurrValues->connectableci_id."'\n", FILE_APPEND);
			GenericCommFunct::UpdateCIDependencies($oObject->connectableci_id);
		}
	}
	public function OnDBInsert($oObject, $oChange = null)
	{
		// only for Generic interfaces
		if(($oObject instanceof GenericCommInterface) === false) { return; }
		if (isset($oObject->m_aCurrValues->connectableci_id))
		{
			GenericCommFunct::UpdateCIDependencies($oObject->m_aCurrValues->connectableci_id);
		}
	}
	public function OnDBDelete($oObject, $oChange = null)
	{
		// only for Generic interfaces
		if(($oObject instanceof GenericCommInterface) === false) { return; }
		if (isset($oObject->connectableci_id))
		{
			GenericCommFunct::UpdateCIDependencies($oObject->m_aCurrValues->connectableci_id);
		}
	}
}

class GenericCommFunct
{
	/**
	 * Hopefully an external class for all big functions in that package
	 */

	public function IterateVirtInterfaces($nLevel, $nInt_id, $nRealInt_id, $aVirtInterfaces)
	{
		$nLevel++ ;
		$sOQL = "SELECT lnkGenericCommInterfaceToGenericCommVirtInterface WHERE genericcomminterface_id = :interface";
		$oLnkVirtInterfaceSet = new DBObjectSet(DBObjectSearch::FromOQL($sOQL), array(), array('interface' => $nInt_id));
		while ($oLnkVirtInterface = $oLnkVirtInterfaceSet->Fetch())
		{
			$sOQL2 = "SELECT GenericCommVirtInterface WHERE id = :interface";
			$oVirtInterfaceSet = new DBObjectSet(DBObjectSearch::FromOQL($sOQL2), array(), array('interface' => $oLnkVirtInterface->Get('genericcommvirtinterface_id')));
			while ($oVirtInterface = $oVirtInterfaceSet->Fetch())
			{
				if ($oVirtInterface->Get('virttogenericredundancy') != NULL && $oVirtInterface->Get('virttogenericredundancy') != 'disabled')
				{
					$aVirtInterfaces[$oLnkVirtInterface->Get('genericcommvirtinterface_id')][] = array('level' => $nLevel, 'GenInt' => $nRealInt_id, 'VirtRedundancy' => $oVirtInterface->Get('virttogenericredundancy'));
					$aVirtInterfaces = GenericCommFunct::IterateVirtInterfaces($nLevel, $oLnkVirtInterface->Get('genericcommvirtinterface_id'), $oLnkVirtInterface->Get('genericcommvirtinterface_id'), $aVirtInterfaces);
				}
				else 
				{
					$aVirtInterfaces = GenericCommFunct::IterateVirtInterfaces($nLevel-1, $oLnkVirtInterface->Get('genericcommvirtinterface_id'), $nRealInt_id, $aVirtInterfaces);
				}
			}
		}
		return $aVirtInterfaces;
	}

	public function UpdateCIDependencies($device_id, $searchImpact = TRUE)
	{
		// $sDebugFile=$_SERVER['CONTEXT_DOCUMENT_ROOT']."/debug/dd-".date("Y-m-d").".txt";
		// file_put_contents($sDebugFile, "BEGIN : ".date("H:i:s")."\n", FILE_APPEND);
		// file_put_contents($sDebugFile, "In the GenericCommInterface Class for the device ".$device_id."\n", FILE_APPEND);
		// get all GenericCommInterface of the current device
		$aConnDevImpacts = array();
		$aConnDevDepends = array();
		$aVirtInterfaces = array();
		$oDevice = MetaModel::GetObject('ConnectableCI', $device_id);
		if (is_object($oDevice))
		{
			// step 1 : collect the configuration for this device
			$sOQL = "SELECT	GenericCommPhysInterface WHERE connectableci_id = :device";
			$oPhysInterfaceSet = new DBObjectSet(DBObjectSearch::FromOQL($sOQL),array(),array('device' => $device_id));
			while ($oPhysInterface = $oPhysInterfaceSet->Fetch())
			{
				if ($oPhysInterface->Get('connected_to_id') != 0) 
				{
					if ($oPhysInterface->Get('connection_impact') == 'depends')
					{
						$aConnDevDepends[$oPhysInterface->GetKey()] = array('remoteDev' => $oPhysInterface->Get('connected_to_device_id'), 'remoteInt' => $oPhysInterface->Get('connected_to_id'));
						$aVirtInterfaces = GenericCommFunct::IterateVirtInterfaces(0, $oPhysInterface->GetKey(), $oPhysInterface->GetKey(), $aVirtInterfaces);
					}
					else 
					{
						$aConnDevImpacts[$oPhysInterface->Get('connected_to_device_id')] = '';
					}
				}
			}
			// file_put_contents($sDebugFile, "Contents of the array \$aConnDevDepends (list of Devices impacting this device)\n", FILE_APPEND);
			// file_put_contents($sDebugFile, print_r($aConnDevDepends, true), FILE_APPEND);
			// file_put_contents($sDebugFile, "Contents of the array \$aConnDevImpacts (list of Devices Depending of this device)\n", FILE_APPEND);
			// file_put_contents($sDebugFile, print_r($aConnDevImpacts, true), FILE_APPEND);
			// file_put_contents($sDebugFile, "Contents of the array \$aVirtInterfaces (list of all virtual interfaces this device)\n", FILE_APPEND);
			// file_put_contents($sDebugFile, print_r($aVirtInterfaces, true), FILE_APPEND);
			// now, build the link matrix
			$aDependDevice = array();
			$aDirectConnDevDepends = $aConnDevDepends; 
			foreach($aVirtInterfaces as $aVirt)
			{
				$aTmp = array('Redundancy' => '', 'remoteDev' => array());
				foreach($aVirt as $aGen)
				{
					if (array_key_exists($aGen['GenInt'],$aConnDevDepends)) 
					{
						$aTmp['Redundancy'] = $aGen['VirtRedundancy'];
						// Store the RemoteDev as a key to filter multiple link
						$aTmp['remoteDev'][$aConnDevDepends[$aGen['GenInt']]['remoteDev']] = '';
						if (array_key_exists($aGen['GenInt'], $aDirectConnDevDepends)) { unset($aDirectConnDevDepends[$aGen['GenInt']]); }
					}
				}
				$bPush = TRUE;
				foreach( $aDependDevice as $aValid )
				{
					if ($aValid['Redundancy'] == $aTmp['Redundancy'] && $aValid['remoteDev'] == $aTmp['remoteDev'] ) { $bPush = FALSE; }
				}
				if ($bPush && $aTmp['Redundancy'] != '') { $aDependDevice[] = $aTmp; }
			}
			// there still a cleanup to do in case of more than one connection between two devices
			$aDirectConnectDevices = array();
			foreach ($aDirectConnDevDepends as $nLocalInt) { $aDirectConnectDevices[$nLocalInt['remoteDev']] = '';}
			// file_put_contents($sDebugFile, "Contents of the array \$aDependDevice (list of redundant connections of this device)\n", FILE_APPEND);
			// file_put_contents($sDebugFile, print_r($aDependDevice, true), FILE_APPEND);
			// file_put_contents($sDebugFile, "Contents of the array \$aDirectConnectDevices (list distant devices of this device with a non redundant connection)\n", FILE_APPEND);
			// file_put_contents($sDebugFile, print_r($aDirectConnectDevices, true), FILE_APPEND);
			// I now have all the datas that must be put in the lnkTables (if not present) :
			// All direct connections should be in the lnkConnectableCIToConnectableCI0, in the form 'this device in dependantci_id and $aDirectConnectDevices[*]
			// all kind of redundant connections should be in the $aDependDevice array : 
			// aDependDevice[*]['Redundancy'] contains the redundancy type
			// aDependDevice[*]['remoteDev'] is an array with all destination Devices
			// for the connection that impacts, it's not possible to determine the impact from here
			// so it will be necessary to re run this script for each impacted device, according to the $aConnDevImpacts array.

			// Step 2 : scan all lnk tables, to gather the existing connection for the current device
			// Only in case this device depends, because the case impacts will be seen from the impacted device point of view.
			// First the non redundant connections (in table 0)
			$sOQL = "SELECT	lnkConnectableCIToConnectableCI0 WHERE dependantci_id = :device";
			$oLnkTableSet0 = new DBObjectSet(DBObjectSearch::FromOQL($sOQL), array(), array('device' => $device_id));
			// file_put_contents($sDebugFile, "lnkConnectableCIToConnectableCI0->Count() (Dependant, non redundant) = ".$oLnkTableSet0->Count()."\n", FILE_APPEND);
			while ($oLnkTable = $oLnkTableSet0->Fetch())
			{
				if ( array_key_exists($oLnkTable->Get('impactorci_id'), $aDirectConnectDevices) ) 
				{
					// OK, link exists in the device and in the table
					unset($aDirectConnectDevices[$oLnkTable->Get('impactorci_id')]);
					// file_put_contents($sDebugFile, "Remote impactor device ".$oLnkTable->Get('impactorci_id')." exists in both the device and the table, nothing to do\n", FILE_APPEND);
				}
				else
				{
					// Link exists in the table but not anymore in the device, to remove
					// file_put_contents($sDebugFile, "Remote impactor device ".$oLnkTable->Get('impactorci_id')." exists in the table, but not in the device, has to be removed from the table\n", FILE_APPEND);
					$oLnkTable->DBDelete();
				}
			}
			// link to add ? Yes if $aDirectConnectDevices is not empty
			foreach ($aDirectConnectDevices as $remoteDev => $nothing)
			{
				//each remaining $remoteDev should be linked in the lnkTables
				// file_put_contents($sDebugFile, "Remote impactor device ".$remoteDev." exists in the device, but not in the table, has to be created in the table\n", FILE_APPEND);
				if ($remoteDev > 0 && $device_id >0 && $remoteDev != $device_id)
				{
					$oNewLink = new lnkConnectableCIToConnectableCI0();
					$oNewLink->Set('impactorci_id', $remoteDev);
					$oNewLink->Set('dependantci_id', $device_id);
					$oNewLink->DBInsert();
				}
			}

			//then the redundant links
			$aFree = array('1' =>'','2' =>'','3' =>'','4' =>'','5' =>'','6' =>'','7' =>'','8' =>'','9' =>'');
			$oLocalDevice = MetaModel::GetObject('ConnectableCI', $device_id);
			for ($i=1; $i<10; $i++)
			{
				$sOQL = "SELECT lnkConnectableCIToConnectableCI".$i." WHERE dependantci_id = :device";
				$oLnkTableSet = new DBObjectSet(DBObjectSearch::FromOQL($sOQL), array(), array('device' => $device_id));
				//file_put_contents($sDebugFile, "lnkConnectableCIToConnectableCI".$i."->Count() (Dependant, redundant) = ".$oLnkTableSet->Count()."\n", FILE_APPEND);
				// remove unneeded connection
				if ($oLnkTableSet->Count() >0)
				{
					$aRemoteDevices = array();
					while ($oLnkTable = $oLnkTableSet->Fetch())
					{
						$aRemoteDevices[$oLnkTable->Get('impactorci_id')]='';
					}
					ksort($aRemoteDevices);
					$sRedName = "GenCommRedundancy".$i;
					$sRedundancy = $oLocalDevice->Get($sRedName);
					//file_put_contents($sDebugFile, "Link TBL ".$i." : Redundancy ".$sRedundancy.", Devices ".print_r($aRemoteDevices,true)."\n", FILE_APPEND);
					$bPush = TRUE;
					foreach ($aDependDevice as $iDepKey => $aDepData)
					{
						$aCurrRemoteDevices = $aDepData['remoteDev'];
						ksort($aCurrRemoteDevices);
						//file_put_contents($sDebugFile, "Current Device : Redundancy ".$aDepData['Redundancy'].", Devices ".print_r($aCurrRemoteDevices,true)."\n", FILE_APPEND);
						if ($aDepData['Redundancy'] == $sRedundancy && $aRemoteDevices == $aCurrRemoteDevices) 
						{ 
							$bPush = FALSE;
							$iDepKeyToRemove = $iDepKey;
						}
					}
					if ($bPush)
					{
						// found a link set not present on the device. The links are to remove, the redundancy mode can stay
						// file_put_contents($sDebugFile, "The link set number ".$i." is not present on the device, I have to remove it.\n", FILE_APPEND);
						$oLnkTableSet2 = new DBObjectSet(DBObjectSearch::FromOQL($sOQL), array(), array('device' => $device_id));
						while ($oLnkTable2 = $oLnkTableSet2->Fetch())
						{
							// file_put_contents($sDebugFile, "Remove the link ".$oLnkTable2->Get('impactorci_id')." -> ".$device_id." in link set number ".$i."\n", FILE_APPEND);
							$aRemoteDevices[$oLnkTable2->Get('impactorci_id')]='';
							$oLnkTable2->DBDelete();
						}
					}
					else
					{
						// the current link exists already in the table, "nothing" to do
						// file_put_contents($sDebugFile, "The link set number ".$i." is the same as the entry ".$iDepKey.", nothing to do.\n", FILE_APPEND);
						unset($aDependDevice[$iDepKeyToRemove]);
						unset($aFree[$i]);
					}
				}
			}
			// at this point, all unneeded link set to this device are removed, and the remaining data in $aDependDevice need to be created
			foreach ($aDependDevice as $aData)
			{
				$nFreeSet = min(array_keys($aFree));
				unset($aFree[$nFreeSet]);
				foreach ($aData['remoteDev'] as $remoteDev => $empty)
				{
					// file_put_contents($sDebugFile, "Add the remote device : ".$remoteDev." and the redundancy ".$aData['Redundancy']." in link set number ".$nFreeSet."\n", FILE_APPEND);
					if ($remoteDev > 0 && $device_id >0 && $remoteDev != $device_id)
					{
						$sNewLinkName = "lnkConnectableCIToConnectableCI".$nFreeSet;
						$oNewLink = new $sNewLinkName();
						$oNewLink->Set('impactorci_id', $remoteDev);
						$oNewLink->Set('dependantci_id', $device_id);
						$oNewLink->DBInsert();
					}
				}
				// the redundancy type should actually be changed here, out of the loop
				$sRedName = "GenCommRedundancy".$nFreeSet;
				$oLocalDevice->Set($sRedName, $aData['Redundancy']);
				$oLocalDevice->DBUpdate();
			}
			// It's now time to call the function for the dependant devices, if any
			if ( $searchImpact && count($aConnDevImpacts) > 0 )
			{
				// file_put_contents($sDebugFile, "This device impacts ".count($aConnDevImpacts).", calling myself for them\n", FILE_APPEND);
				foreach ($aConnDevImpacts as $nDependantDevice => $empty )
				{
					GenericCommFunct::UpdateCIDependencies($nDependantDevice, FALSE);
				}
			}
		}
	}
}