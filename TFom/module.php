<?php
class TFopenmower extends IPSModule
{
    
    public function Create(){
        parent::Create();
        $this->ConnectParent("{F7A0DD2E-7684-95C0-64C2-D2A9DC47577B}");
		if (!IPS_VariableProfileExists('TFOM.volt')) 
		{
			IPS_CreateVariableProfile('TFOM.volt', 2);
			IPS_SetVariableProfileIcon ('TFOM.volt', 'Battery');
			IPS_SetVariableProfileDigits('TFOM.volt', 2);
            IPS_SetVariableProfileText('TFOM.volt', '', ' V');
			IPS_SetVariableProfileValues('TFOM.volt', 0, 30, 0.01);
		}
		if (!IPS_VariableProfileExists('TFOM.ampere')) 
		{
			IPS_CreateVariableProfile('TFOM.ampere', 2);
			IPS_SetVariableProfileIcon ('TFOM.ampere', 'Electricity');
			IPS_SetVariableProfileDigits('TFOM.ampere', 2);
            IPS_SetVariableProfileText('TFOM.ampere', '', ' A');
			IPS_SetVariableProfileValues('TFOM.ampere', 0, 10, 0.01);
		}
		
        if (!IPS_VariableProfileExists('TFOM.state')) 
		{
            IPS_CreateVariableProfile('TFOM.state', 1);
			IPS_SetVariableProfileIcon ('TFOM.state', 'Database');
            IPS_SetVariableProfileAssociation('TFOM.state', 0, 'Ruhe', 'Sleep', -1); // IDLE
			IPS_SetVariableProfileAssociation('TFOM.state', 1, 'Docking', 'Garage', -1); // DOCKING
			IPS_SetVariableProfileAssociation('TFOM.state', 2, 'Undocking', 'Hollowdoublearrowup', -1); // UNDOCKING
			IPS_SetVariableProfileAssociation('TFOM.state', 3, 'Mähe', 'Menu', -1); // MOWING
			IPS_SetVariableProfileAssociation('TFOM.state', 4, 'Karte / Fahrmodus', 'Move', -1); // AREA_RECORDING
        }
		if (!IPS_VariableProfileExists('TFOM.emergency')) 
		{
            IPS_CreateVariableProfile('TFOM.emergency', 0);
			IPS_SetVariableProfileIcon ('TFOM.emergency', 'Alert');
            IPS_SetVariableProfileAssociation('TFOM.emergency', true, 'Alarm', 'Alarm', -1);
			IPS_SetVariableProfileAssociation('TFOM.emergency', false, 'OK', 'Ok', -1);
		}
		if (!IPS_VariableProfileExists('TFOM.is_charging')) 
		{
            IPS_CreateVariableProfile('TFOM.is_charging', 0);
			IPS_SetVariableProfileIcon ('TFOM.is_charging', 'Electricity');
            IPS_SetVariableProfileAssociation('TFOM.is_charging', true, 'Akku wird geladen', 'EnergyProduction', -1);
			IPS_SetVariableProfileAssociation('TFOM.is_charging', false, 'Entläd', 'Close', -1);
		}
		if (!IPS_VariableProfileExists('TFOM.actions')) 
		{
            IPS_CreateVariableProfile('TFOM.actions', 1);
			IPS_SetVariableProfileIcon ('TFOM.actions', 'Remote');
			IPS_SetVariableProfileAssociation('TFOM.actions', 1, 'Mähen', 'Menu', -1);
			IPS_SetVariableProfileAssociation('TFOM.actions', 2, 'Mähen abbrechen', 'Close', -1);
			IPS_SetVariableProfileAssociation('TFOM.actions', 3, 'Mähen pausieren', 'Sleep', -1);
			IPS_SetVariableProfileAssociation('TFOM.actions', 4, 'Mähen fortsetzen', 'Climate', -1);
			IPS_SetVariableProfileAssociation('TFOM.actions', 5, 'Nächste Fläche mähen', 'Hollowdoublearrowright', -1);
        }
	}

    
    public function ApplyChanges()
	{
        parent::ApplyChanges();
		//$progress_percentID 	= $this->RegisterVariableFloat("progress_percent", "Fortschritt", "~Progress");
		$battery_percentID 		= $this->RegisterVariableFloat("battery_percent", "Akkustand", "~Progress", 1);
		$batteryID 				= $this->RegisterVariableFloat("battery", "Akkuspannung", "TFOM.volt", 2);
		$stateID 				= $this->RegisterVariableInteger("state", "Status", "TFOM.state", 3);
		$actionID 				= $this->RegisterVariableInteger("action", "Aktionen", "TFOM.actions", 4);

		$emergencyID 			= $this->RegisterVariableBoolean("emergency", "Notaus", "TFOM.emergency", 5);
		$is_chargingID 			= $this->RegisterVariableBoolean("is_charging", "Ladestatus", "TFOM.is_charging", 6);
		$chargeVID 				= $this->RegisterVariableFloat("chargeV", "Ladespannung", "TFOM.volt", 7);
		$chargeAID 				= $this->RegisterVariableFloat("chargeA", "Ladestrom", "TFOM.ampere", 8);

		$escL_tempID 			= $this->RegisterVariableFloat("escL_temp", "Temp ESC Links", "~Temperature", 9);
		$escR_tempID 			= $this->RegisterVariableFloat("escR_temp", "Temp ESC Rechts", "~Temperature", 10);
		$escM_tempID 			= $this->RegisterVariableFloat("escM_temp", "Temp ESC Messer", "~Temperature", 11);
		$mow_tempID 			= $this->RegisterVariableFloat("mow_temp", "Temp Messermotor", "~Temperature", 12);

		$mowAID 				= $this->RegisterVariableFloat("mowA", "Messermotorstrom", "TFOM.ampere", 13);
		$gps_percentID 			= $this->RegisterVariableFloat("gps_percent", "GPS Qualität", "~Progress", 14);

		$this->EnableAction("action");
    }
	
	public function Test()
	{
		//$this->SendCMD("/action", "mower_logic:idle/start_mowing");
		//$this->SendCMD("/action", "mower_logic:mowing/abort_mowing");
	}
		
    public function ReceiveData($JSONString)
    {
		$data = json_decode($JSONString, true);
		if($data['DataID'] == '{7F7632D9-FA40-4F38-8DEA-C83CD4325A32}')
		{
			//$this->SendDebug("AllTopics", $data['Topic'], 0);
			$topic = explode('/', $data['Topic']);
			if($topic[0] == "robot_state" && $topic[1] == "json")
			{
				$valueData = json_decode($data["Payload"], true);
				$this->SendDebug("robot_state",$data["Payload"], 0);
				switch($valueData["current_state"])
				{
					case "IDLE" 			: $this->SetValue("state", 0);break;
					case "DOCKING" 			: $this->SetValue("state", 1);break;
					case "UNDOCKING" 		: $this->SetValue("state", 2);break;
					case "MOWING" 			: $this->SetValue("state", 3);break;
					case "AREA_RECORDING" 	: $this->SetValue("state", 4);break;
				}
				$this->SetValue("battery_percent", $valueData["battery_percentage"] * 100);
				#$this->SetValue("progress_percent", $valueData["current_action_progress"] * 100);
				$this->SetValue("emergency", $valueData["emergency"]);
				$this->SetValue("is_charging", $valueData["is_charging"]);
				$this->SetValue("gps_percent", $valueData["gps_percentage"] * 100);
			}
			else if($topic[0] == "sensors")
			{
				if($topic[1] == "om_v_battery" && $topic[2] == "data")
				{
					$this->SetValue("battery", $data["Payload"]);
					//$this->SendDebug("Empfange",$data["Payload"], 1);
				}
				else if($topic[1] == "om_v_charge" && $topic[2] == "data")
				{
					if(boolval($this->GetValue("is_charging")))
					{
						$this->SetValue("chargeV", $data["Payload"]);
					}
					else
					{
						$this->SetValue("chargeV", 0);
					}
				}
				else if($topic[1] == "om_charge_current" && $topic[2] == "data")
				{
					if(boolval($this->GetValue("is_charging")))
					{
						$this->SetValue("chargeA", $data["Payload"]);
					}
					else
					{
						$this->SetValue("chargeA", 0);
					}
				}
				else if($topic[1] == "om_left_esc_temp" && $topic[2] == "data")
				{
					$this->SetValue("escL_temp", $data["Payload"]);
				}
				else if($topic[1] == "om_right_esc_temp" && $topic[2] == "data")
				{
					$this->SetValue("escR_temp", $data["Payload"]);
				}
				else if($topic[1] == "om_mow_esc_temp" && $topic[2] == "data")
				{
					$this->SetValue("escM_temp", $data["Payload"]);
				}
				else if($topic[1] == "om_mow_motor_temp" && $topic[2] == "data")
				{
					$this->SetValue("mow_temp", $data["Payload"]);
				}
				else if($topic[1] == "om_mow_motor_current" && $topic[2] == "data")
				{
					$this->SetValue("mowA", $data["Payload"]);
				}
				else
				{				
					//$this->SendDebug("Sonstiges",$data["Payload"], 0);
				}
			}
			else if($topic[0] == "actions" && $topic[1] == "json")
			{
				$this->SendDebug("Actions", $data["Payload"], 0);	
			}
			else
			{
				if($topic[1] != "bson") // No Binary Data !
				{
					$this->SendDebug("Topics", $data['Topic'], 0);	
				}
			}
		}    
    }
	
	public function SendCMD(string $topic, string $value)
	{
		if(strlen($topic) > 3)
		{
			//$deviceTopic				= $this->ReadPropertyString("deviceTopic");
			$data['DataID'] 			= '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
			$data['PacketType'] 		= 3;
			$data['QualityOfService'] 	= 0;
			$data['Retain'] 			= false;
			$data['Topic'] 				= $topic;
			$data['Payload'] 			= strval($value);
			$dataJSON 					= json_encode($data,JSON_UNESCAPED_SLASHES);
			$this->SendDataToParent($dataJSON);
		}
	}
	
	public function RequestAction($ident, $value) 
	{
		switch($ident)
		{
			case "action" :
				switch($value)
				{
					case 1 : $mqttVal = "mower_logic:idle/start_mowing";break;
					case 2 : $mqttVal = "mower_logic:mowing/abort_mowing";break;
					case 3 : $mqttVal = "mower_logic:mowing/pause";break;
					case 4 : $mqttVal = "mower_logic:mowing/continue";break;
					case 5 : $mqttVal = "mower_logic:mowing/skip_area";break;
				}
			break;
		}
		$this->SendCMD("/action", $mqttVal);
	}
}
?>