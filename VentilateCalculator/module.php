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
        $this->RegisterPropertyInteger('AirPressureId', 0);
		
		// Co2 handling
        $this->RegisterPropertyInteger('Co2ValueStarting', 0);
        $this->RegisterPropertyInteger('Co2ValueStopping', 0);
		
        $this->RegisterPropertyInteger('CheckInterval', 15);
        $this->RegisterPropertyFloat('DiffValue', 0);

        //Timer
        $this->RegisterTimer('CheckTimer', $this->ReadPropertyInteger('CheckInterval') * 1000, 'VC_TimerDone($_IPS[\'TARGET\']);');

        if (!IPS_VariableProfileExists('VC.Ventilate')) {
            IPS_CreateVariableProfile('VC.Ventilate', 0);
            IPS_SetVariableProfileAssociation('VC.Ventilate', 0, $this->Translate('No ventilation'), 'NoVentilation', -1);
            IPS_SetVariableProfileAssociation('VC.Ventilate', 1, $this->Translate('Ventilation'), 'Ventilation', -1);
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

		if (GetValueBoolean($this->GetIDForIdent('Active')) == 1){
			$timerIntervalInMilliSec = $this->ReadPropertyInteger('CheckInterval') * 1000;
			$this->SetTimerInterval('CheckTimer', $timerIntervalInMilliSec);
			$this->SendDebug("ApplyChanges", "Update timer to " .$timerIntervalInMilliSec. "ms", 0);
		}
    }
	
	public function CalculateAirVentilation(){
		$innerTemp = GetValueFloat($this->ReadPropertyInteger('InnerTemperatureId'));
		$innerRelativeHumidity = GetValueFloat($this->ReadPropertyInteger('InnerHumidityId'));

		$outerTemp = GetValueFloat($this->ReadPropertyInteger('OuterTemperatureId'));
		$outerRelativeHumidity = GetValueFloat($this->ReadPropertyInteger('OuterHumidityId'));
		
		$airPressure = GetValueFloat($this->ReadPropertyInteger('AirPressureId'));
		
		$shallBeAiredValue = shallBeAired(getAbsoluteHumidity($outerTemp, $outerRelativeHumidity, $airPressure), getAbsoluteHumidity($innerTemp, $innerRelativeHumidity, $airPressure));

		$this->SendDebug("CalculateAirVentilation", "Calculated the ventilation, result: ".$shallBeAiredValue, 0);
		SetValueBoolean($this->GetIDForIdent('Ventilate'), $shallBeAiredValue);
	}

    public function SetActive(bool $active)
    {
        //Modul aktivieren
        SetValue($this->GetIDForIdent('Active'), $active);
		
		if ($active) {
			$timerIntervalInMilliSec = $this->ReadPropertyInteger('CheckInterval') * 1000;
			$this->SetTimerInterval('CheckTimer', $timerIntervalInMilliSec);
			$this->SendDebug("SetActive", "Enabled next timer in " .$timerIntervalInMilliSec. "ms", 0);
        } else {
			$this->SendDebug("SetActive", "Disabled, disable timer", 0);
			$this->SetTimerInterval('CheckTimer', 0);
		}
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

    public function TimerDone()
    {
		$this->CalculateAirVentilation();
    }
	
	public function getAbsoluteHumidity($temp, $humidity, $airPressure) {
		$airPressure = GetValueFloat($airPressure);
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
		return ((($airPressure / 1000) * $humidity / 100 * $interpolatedValue)/(461.5*$tempInKelvin)*1000);
	}

	public function shallBeAired($absHumidityOuter, $absHumidityInner) {
		return ($absHumidityOuter< $absHumidityInner);
	}
}