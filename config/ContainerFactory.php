<?php


namespace Genesis\Config;


use Nette\Neon\Decoder;


/**
 * @author Adam Bisek <adam.bisek@gmail.com>
 */
class ContainerFactory
{

	private $configs;

	private $containersToMerge;

	private $workingDirectory;


	public function addConfig($file)
	{
		$this->configs[] = $file;
	}


	public function getConfigs()
	{
		return $this->configs;
	}


	public function addContainerToMerge(Container $container)
	{
		$this->containersToMerge[] = $container;
	}


	public function getContainersToMerge()
	{
		return $this->containersToMerge;
	}


	public function getWorkingDirectory()
	{
		return $this->workingDirectory;
	}


	public function setWorkingDirectory($workingDirectory)
	{
		$this->workingDirectory = $workingDirectory;
	}


	public function create()
	{
		if (!class_exists('Nette\Neon\Decoder', TRUE)) {
			throw new \RuntimeException("Neon is not loaded.");
		}
		if (empty($this->configs)) {
			throw new \RuntimeException("No config added.");
		}
		$config = [
			'workingDirectory' => $this->workingDirectory,
		];
		if($this->containersToMerge){
			foreach ($this->containersToMerge as $containerToMerge) {
				foreach ($containerToMerge as $k => $v) {
					$config[$k] = $v;
				}
			}
		}
		$neonDecoder = new Decoder;
		foreach ($this->configs as $file) {
			if (!is_file($file)) {
				throw new \RuntimeException("Config file '$file' not found.");
			}
			if (!is_readable($file)) {
				throw new \RuntimeException("Config file '$file' not readable.");
			}
			$array = $neonDecoder->decode(file_get_contents($file));
			if($array !== NULL){
				$config = array_replace_recursive($config, $array);
			}
		}
		$config = $this->parseValues($config);
		return new Container($config);
	}


	private function parseValues($config, & $allConfig = [], $keysPath = [])
	{
		foreach ($config as $key => $value) {
			if (is_array($value)) {
				$value = $this->parseValues($value, $allConfig, array_merge($keysPath, [$key]));
			} elseif(!is_object($value)) {
				$value = $this->parseValue($value, $allConfig);
			}

			// get new key name, and replace it
			$newKey = $this->parseValue($key, $allConfig);
			unset($config[$key]);
			$config[$newKey] = $value;

			// write to global config
			$v =& $allConfig;
			foreach ($keysPath as $kp) {
				$v =& $v[$kp];
			}
			$v[$newKey] = $value;
		}
		return $config;
	}


	private function parseValue($value, $config)
	{
		if (preg_match_all('#%([^%]+)%#', $value, $matches)) {
			foreach ($matches[1] as $match) {
				if (!array_key_exists($match, $config)) {
					throw new \RuntimeException("Cannot find variable '$match'.");
				}
				$value = str_replace("%$match%", $config[$match], $value); // TODO: nested variables
			}
		}
		return $value;
	}

}