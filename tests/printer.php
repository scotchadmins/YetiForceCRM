<?php

/**
 * Travis CI result printer class
 * @package YetiForce.Test
 * @copyright YetiForce Sp. z o.o.
 * @license YetiForce Public License 2.0 (licenses/License.html or yetiforce.com)
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
// @codeCoverageIgnoreStart
class YtResultPrinter extends PHPUnit\TextUI\ResultPrinter
{

	/**
	 * A test started.
	 *
	 * @param Test $test
	 */
	public function startTest(PHPUnit\Framework\Test $test)
	{
		if ($this->debug) {
			$this->write("\n" . \get_class($test) . '::' . $test->getName());
			//$this->write(\sprintf("\n%s", \PHPUnit\Util\Test::describe($test)));
		}
	}

	public function printResult(\PHPUnit\Framework\TestResult $result)
	{
		$this->write("\n==========================================================================================================");
		parent::printResult($result);
		$this->write("\n==========================================================================================================");
	}
}

// @codeCoverageIgnoreEnd
