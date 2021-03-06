<?php


namespace Genesis;


use Genesis\Commands;
use Genesis\Config;

/**
 * @author Adam Bisek <adam.bisek@gmail.com>
 */
class Build implements IBuild
{

	/** @var Config\Container */
	private $container;

	/** @var array|NULL */
	private $arguments;


	public function __construct(Config\Container $container, array $arguments = NULL)
	{
		$this->container = $container;
		$this->arguments = $arguments;
	}


	/**
	 * @return Config\Container
	 */
	public function getContainer()
	{
		return $this->container;
	}


	/**
	 * @return array|NULL
	 */
	public function getArguments()
	{
		return $this->arguments;
	}

	/**
	 * Back compatibility.
	 * @TODO: remove in version 3.x
	 * @codeCoverageIgnoreStart
	 */
	public function &__get($name)
	{
		if(in_array($name, ['container', 'arguments'])){
			$method = 'get' . ucfirst($name);
			trigger_error(E_USER_WARNING, "Property '$name' is deprecated, use method $method() instead.");
			return $this->$method();
		}
		trigger_error(E_USER_WARNING, "Property '$name' is not defined.");
	}//@codeCoverageIgnoreEnd

	public function setup()
	{
	}


	public function runDefault()
	{
		$helpCommand = new Commands\Help;
		foreach ($this->detectAvailableTasks() as $section => $tasks) {
			if (!$helpCommand->hasSection($section)) {
				$helpCommand->addSection($section);
			}
			$helpCommand->setSectionTasks($section, $tasks);
		}
		$helpCommand->execute();
	}


	protected function detectAvailableTasks()
	{
		$tasks = [];
		$classReflection = new \ReflectionClass($this);
		foreach ($classReflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
			if (preg_match('#^run(.*)#', $method->name, $match)) {
				$doc = $method->getDocComment();
				$section = NULL;
				if (preg_match('#@section ?([^\s]*)\s#s', $doc, $m)) {
					$section = trim($m[1]);
				}
				$description = NULL;
				if (preg_match('#([^@][a-zA-Z0-9]+)+#', $doc, $m)) {
					$description = trim($m[0]);
				}
				$tasks[$section][lcfirst($match[1])] = $description != '' ? $description : NULL;
			}
		}
		return $tasks;
	}


	protected function error($message)
	{
		throw new ErrorException($message);
	}


	protected function logSection($message)
	{
		echo Cli::getColoredString("=> " . $message, 'green') . PHP_EOL;
	}


	protected function log($message)
	{
		echo $message . PHP_EOL;
	}

}