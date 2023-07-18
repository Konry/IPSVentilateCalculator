<?php

declare(strict_types=1);

/**
 * @method RegisterPropertyBoolean(string $string, int $int)
 * @method RegisterPropertyFloat(string $string, int $int)
 * @method RegisterPropertyInteger(string $string, int $int)
 * @method RegisterTimer(string $string, float|int $param, string $string1)
 * @method ReadPropertyInteger(string $string)
 * @method Translate(string $string)
 * @method SetTimerInterval(string $string, float|int $timerIntervalInMilliSec)
 * @method SendDebug(string $string, string $string1, int $int)
 * @method GetIDForIdent(string $string)
 * @method ReadPropertyFloat(string $string)
 * @method ReadPropertyBoolean(string $string)
 */
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
        $this->RegisterPropertyBoolean('RelativeHumidityMode', 0);
        $this->RegisterPropertyFloat('RelativeHumidityYellowRange', 60);
        $this->RegisterPropertyFloat('RelativeHumidityRedRange', 70);

        $this->RegisterPropertyBoolean('CO2Mode', 0);
        $this->RegisterPropertyInteger('CO2YellowLevel', 1200);
        $this->RegisterPropertyInteger('CO2RedLevel', 2000);
		
        $this->RegisterPropertyInteger('CheckInterval', 15);
        //$this->RegisterPropertyFloat('DiffValue', 0);

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
        $this->RegisterVariableFloat('VentilationScale', $this->Translate('VentilationScale'), '~Valve.F');
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
	
	private function CalculateAirVentilation(): bool
    {
		$innerTemp = GetValue($this->ReadPropertyInteger('InnerTemperatureId'));
		$innerRelativeHumidity = GetValue($this->ReadPropertyInteger('InnerHumidityId'));

		$outerTemp = GetValue($this->ReadPropertyInteger('OuterTemperatureId'));
		$outerRelativeHumidity = GetValue($this->ReadPropertyInteger('OuterHumidityId'));
		
		$airPressure = GetValue($this->ReadPropertyInteger('AirPressureId'));
		
		$absoluteHumidityOuter = $this->GetAbsoluteHumidity($outerTemp, $outerRelativeHumidity, $airPressure);
		$absoluteHumidityInner = $this->GetAbsoluteHumidity($innerTemp, $innerRelativeHumidity, $airPressure);
		
		$shallBeAiredValue = $this->ShallBeAired($absoluteHumidityOuter, $absoluteHumidityInner);

		$this->SendDebug("CalculateAirVentilation", "Calculated the ventilation, result: ".$shallBeAiredValue, 0);
		SetValueBoolean($this->GetIDForIdent('Ventilate'), $shallBeAiredValue);
        return $shallBeAiredValue;
	}

    private function CalculateVentilationScaleCo2() : float {
        $co2Mode = $this->ReadPropertyBoolean('CO2Mode');
        if($co2Mode == 0){
            return -1;
        }
        $co2YellowRange = $this->ReadPropertyInteger('CO2YellowLevel');
        $co2RedRange = $this->ReadPropertyInteger('CO2RedLevel');

        $innerRelativeCo2 = GetValue($this->ReadPropertyInteger('InnerCo2Id'));

        if($innerRelativeCo2 < $co2YellowRange){
            return 0;
        }elseif ($innerRelativeCo2 > $co2RedRange){
            return 100;
        }
        $scaledDownCo2 = $innerRelativeCo2 - $co2YellowRange;
        $upperLevel = $co2RedRange - $co2YellowRange;

        return 100 * ($scaledDownCo2 / $upperLevel) ;
    }

    private function CalculateVentilationScaleHumidity() : float {
        $humidityMode = $this->ReadPropertyBoolean('RelativeHumidityMode');
        if($humidityMode == 0){
            return -1;
        }
        $humidityYellowRange = $this->ReadPropertyInteger('RelativeHumidityYellowRange');
        $humidityRedRange = $this->ReadPropertyInteger('RelativeHumidityRedRange');

        $innerRelativeHumidity = GetValue($this->ReadPropertyInteger('InnerHumidityId'));

        if($innerRelativeHumidity < $humidityYellowRange){
            return 0;
        }elseif ($innerRelativeHumidity > $humidityRedRange){
            return 100;
        }
        $scaledDownHumidity = $innerRelativeHumidity - $humidityYellowRange;
        $upperLevel = $humidityRedRange - $humidityYellowRange;

        return 100 * ($scaledDownHumidity / $upperLevel) ;
    }


    private function SetActive(bool $active)
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
		$ventilate = $this->CalculateAirVentilation();
        $this->SendDebug("TimerDone", "Calculate Air Ventilation " .$ventilate, 0);
        $valueCo2 = $this->CalculateVentilationScaleCo2();
        $this->SendDebug("TimerDone", "Calculate Ventilation Scale Co2 " .$valueCo2, 0);
        $valueHumidity = $this->CalculateVentilationScaleHumidity();
        $this->SendDebug("TimerDone", "Calculate Ventilation Scale Humidity " .$valueHumidity, 0);

        $ventilationValue = max($valueCo2, $valueHumidity);
        if($ventilate){
            if($ventilationValue >= 0){
                SetValueFloat($this->GetIDForIdent('Ventilate'), $ventilationValue);
            } else {
                SetValueFloat($this->GetIDForIdent('Ventilate'), 0);
            }
        }else{
            SetValueFloat($this->GetIDForIdent('Ventilate'), 0);
        }
    }
	
	public function GetAbsoluteHumidity($temperature, $humidity, $airPressure) {
		$tempInKelvin = $temperature + 273.15;
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

		$oldValue = -1;
		$interpolatedValue = 0;

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

	private function ShallBeAired($absHumidityOuter, $absHumidityInner) {
		return ($absHumidityOuter< $absHumidityInner);
	}
}