<?php

declare(strict_types=1);
class VentilateCalculator extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Properties
        $this->RegisterPropertyInteger('InnerTemperatureId', 0);
        $this->RegisterPropertyInteger('InnerHumidityId', 0);
        $this->RegisterPropertyInteger('InnerCo2Id', 0);
        $this->RegisterPropertyInteger('OuterTemperatureId', 0);
        $this->RegisterPropertyInteger('OuterHumidityId', 0);
		
		// Co2 handling
        $this->RegisterPropertyInteger('Co2ValueStarting', 0);
        $this->RegisterPropertyInteger('Co2ValueStopping', 0);
		
        $this->RegisterPropertyInteger('CheckInterval', 15);
        $this->RegisterPropertyFloat('DiffValue', 0);

        //Timer
        $this->RegisterTimer('CheckTimer', 0, 'VC_TimerDone($_IPS[\'TARGET\']);');

        if (!IPS_VariableProfileExists('VC.Ventilate')) {
            IPS_CreateVariableProfile('VC.Ventilate', 0);
            IPS_SetVariableProfileAssociation('VC.Status', 0, $this->Translate('No ventilation'), 'NoVentilation', -1);
            IPS_SetVariableProfileAssociation('VC.Status', 1, $this->Translate('Ventilation'), 'Ventilation', -1);
        }

        //Updating legacy profiles to also beeing associative
        IPS_SetVariableProfileValues('VC.Ventilate', 0, 0, 0);

        $this->RegisterVariableBoolean('Ventilate', $this->Translate('Ventilate'), 'VC.Ventilate');
        $this->RegisterVariableBoolean('Active', $this->Translate('Active'), '~Switch');
        $this->EnableAction('Active');
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

		/*
        //Deleting outdated events
        $eventID = @$this->GetIDForIdent('EventUp');
        if ($eventID) {
            IPS_DeleteEvent($eventID);
        }

        $eventID = @$this->GetIDForIdent('EventDown');
        if ($eventID) {
            IPS_DeleteEvent($eventID);
        }

        //Unregister all messages in order to read them
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                $this->UnregisterMessage($senderID, $message);
            }
        }
        $this->RegisterMessage($this->ReadPropertyInteger('SourceID'), VM_UPDATE);

        //Add references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }
        if ($this->ReadPropertyInteger('SourceID') != 0) {
            $this->RegisterReference($this->ReadPropertyInteger('SourceID'));
        }
		*/
		$this->SetTimerInterval('CheckTimer', $this->ReadPropertyInteger('CheckInterval') * 1000);
    }
	
	public function DoSomething(){
		
		include(IPS_GetScriptFile(40167));

		$tempInnen = GetValueFloat(15517);
		$relativeLuftfeuchteInnen = GetValue(30332);

		$tempAußen = GetValueFloat(43458);
		$relativeLuftfeuchteAußen = GetValueFloat(40144);

		#echo shallBeAired(getAbsoluteHumidity($tempAußen, $relativeLuftfeuchteAußen), getAbsoluteHumidity($tempInnen, $relativeLuftfeuchteInnen));
		SetBooleanValue(30030, shallBeAired(getAbsoluteHumidity($tempAußen, $relativeLuftfeuchteAußen), getAbsoluteHumidity($tempInnen, $relativeLuftfeuchteInnen)));
		#echo $_IPS['VARIABLE'];
	}

    public function SetActive(bool $Active)
    {
		/*
        if ($this->ReadPropertyInteger('SourceID') == 0) {
            SetValue($this->GetIDForIdent('Status'), 0);

            //Modul Deaktivieren
            SetValue($this->GetIDForIdent('Active'), false);
            echo 'No variable selected';
            return false;
        }

        if ($Active) {
            if (GetValue($this->ReadPropertyInteger('SourceID')) >= $this->ReadPropertyFloat('BorderValue')) {
                SetValue($this->GetIDForIdent('Status'), 1);
                $this->SetBuffer('StatusBuffer', 'Running');
            } else {
                SetValue($this->GetIDForIdent('Status'), 0);
            }
        } else {
            SetValue($this->GetIDForIdent('Status'), 0);
        }
		*/

        //Modul aktivieren
        SetValue($this->GetIDForIdent('Active'), $Active);
        return true;
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Active':
                $this->SetActive($Value);
                break;

            default:
                throw new Exception('Invalid Ident');
        }
    }

/*
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        if (GetValue($this->GetIDForIdent('Active'))) {
            if (($Data[0] < $this->ReadPropertyFloat('BorderValue')) && (GetValue($this->GetIDForIdent('Status')) == 1) && ($this->GetBuffer('StatusBuffer') == 'Running')) {
                $this->SetTimerInterval('CheckTimer', $this->ReadPropertyInteger('Period') * 1000);
                $this->SetBuffer('StatusBuffer', 'TimerDone');
            } elseif ($Data[0] > $this->ReadPropertyFloat('BorderValue')) {
                SetValue($this->GetIDForIdent('Status'), 1);
                $this->SetTimerInterval('CheckTimer', 0);
                $this->SetBuffer('StatusBuffer', 'Running');
            }
        }
    }
	*/

    public function TimerDone()
    {
        echo "execute script"
        //$this->SetTimerInterval('CheckTimer', 0);
    }
	
	public function getAbsoluteHumidity($temp, $humid) {
		$luftdruck = GetValueFloat(36920);
		$tempInKelvin = $temp + 273.15;
		$array = [
			268 => 401.1,
			273 => 610.8,
			278 => 871.8, 
			283 => 1227.1,
			288 => 1704.0,
			290 => 1936.3,
			293 => 2337.0,
			298 => 3167,
			303 => 4241,
		];

		$index = -1;
		$oldValue = -1;
		$interpolatedValue;

		foreach($array as $key => $value )
		{   
			if($tempInKelvin <= $key){
				if($oldValue <> -1){
					$interpolatedValue = ($value + $oldValue) / 2;
				}else{
					$interpolatedValue = $value;
				}
				break;
			}elseif($tempInKelvin >= $key){
				$interpolatedValue = $value;
				$oldValue = $value;
			}
		}
		return ((($airPressure / 1000) * $humid / 100 * $interpolatedValue)/(461.5*$tempInKelvin)*1000);
	}

	public function shallBeAired($absHumidityOuter, $absHumidityInner) {
		return ($absHumidityOuter< $absHumidityInner);
	}
}