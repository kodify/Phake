<?php
/* 
 * Phake - Mocking Framework
 * 
 * Copyright (c) 2010, Mike Lively <mike.lively@sellingsource.com>
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 
 *  *  Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 * 
 *  *  Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 * 
 *  *  Neither the name of Mike Lively nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 * 
 * @category   Testing
 * @package    Phake
 * @author     Mike Lively <m@digitalsandwich.com>
 * @copyright  2010 Mike Lively <m@digitalsandwich.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.digitalsandwich.com/
 */

require_once 'Phake/ClassGenerator/MockClass.php';
require_once 'Phake/CallRecorder/Recorder.php';
require_once 'Phake/Stubber/StubMapper.php';
require_once 'Phake/Stubber/StaticAnswer.php';

require_once 'PhakeTest/MockedClass.php';

/**
 * Description of MockClass
 *
 * @author Mike Lively <m@digitalsandwich.com>
 */
class Phake_ClassGenerator_MockClassTest extends PHPUnit_Framework_TestCase
{
	private $classGen;

	public function setUp()
	{
		$this->classGen = new Phake_ClassGenerator_MockClass();
	}

	/**
	 * Tests the generate method of the mock class generator.
	 */
	public function testGenerateCreatesClass()
	{
		$newClassName = __CLASS__ . '_TestClass1';
		$mockedClass = 'stdClass';

		$this->assertFalse(
			class_exists($newClassName, FALSE),
			'The class being tested for already exists. May have created a test reusing this class name.');

		$this->classGen->generate($newClassName, $mockedClass);

		$this->assertTrue(
			class_exists($newClassName, FALSE),
			'Phake_ClassGenerator_MockClass::generate() did not create correct class');
	}

	/**
	 * Tests that the generate method will create a class that extends a given class.
	 */
	public function testGenerateCreatesClassExtendingExistingClass()
	{
		$newClassName = __CLASS__ . '_TestClass2';
		$mockedClass = 'stdClass';

		$this->classGen->generate($newClassName, $mockedClass);

		$rflClass = new ReflectionClass($newClassName);

		$this->assertTrue(
			$rflClass->isSubclassOf($mockedClass),
			'Phake_ClassGenerator_MockClass::generate() did not create a class that extends mocked class.');
	}

	/**
	 * Tests that generated mock classes will accept and provide access too a call recorder.
	 */
	public function testGenerateCreatesClassWithExposedCallRecorder()
	{
		$newClassName = __CLASS__ . '_TestClass3';
		$mockedClass = 'stdClass';

		$this->classGen->generate($newClassName, $mockedClass);

		$callRecorder = $this->getMock('Phake_CallRecorder_Recorder');
		$stubMapper = $this->getMock('Phake_Stubber_StubMapper');
		$mock = $this->classGen->instantiate($newClassName, $callRecorder, $stubMapper);

		$this->assertSame($callRecorder, $mock->__PHAKE_getCallRecorder());
	}

	/**
	 * Tests that generated mock classes will record calls to mocked methods.
	 */
	public function testCallingMockedMethodRecordsCall()
	{
		$newClassName = __CLASS__ . '_TestClass4';
		$mockedClass = 'PhakeTest_MockedClass';

		$this->classGen->generate($newClassName, $mockedClass);

		$callRecorder = $this->getMock('Phake_CallRecorder_Recorder');
		$stubMapper = $this->getMock('Phake_Stubber_StubMapper');
		$mock = $this->classGen->instantiate($newClassName, $callRecorder, $stubMapper);

		/* @var $callRecorder Phake_CallRecorder_Recorder */
		$callRecorder->expects($this->once())
			->method('recordCall')
			->with($this->equalTo(new Phake_CallRecorder_Call($mock, 'foo')));

		$mock->foo();
	}

	/**
	 * Tests the instantiation functionality of the mock generator.
	 */
	public function testInstantiate()
	{
		$newClassName = __CLASS__ . '_TestClass5';
		$mockedClass = 'PhakeTest_MockedClass';

		$this->classGen->generate($newClassName, $mockedClass);

		$callRecorder = $this->getMock('Phake_CallRecorder_Recorder');
		$stubMapper = $this->getMock('Phake_Stubber_StubMapper');
		$mock = $this->classGen->instantiate($newClassName, $callRecorder, $stubMapper);

		$this->assertType($newClassName, $mock);
	}

	/**
	 * Tests that calling a stubbed method will result in the stubbed answer being returned.
	 */
	public function testStubbedMethodsReturnStubbedAnswer()
	{
		$newClassName = __CLASS__ . '_TestClass7';
		$mockedClass = 'PhakeTest_MockedClass';

		$this->classGen->generate($newClassName, $mockedClass);

		$callRecorder = $this->getMock('Phake_CallRecorder_Recorder');
		$stubMapper = $this->getMock('Phake_Stubber_StubMapper');
		$mock = $this->classGen->instantiate($newClassName, $callRecorder, $stubMapper);

		$answer = $this->getMock('Phake_Stubber_StaticAnswer', array(), array(), '', FALSE);

		$answer->expects($this->once())
			->method('getAnswer');

		$stubMapper->expects($this->once())
			->method('getStubByMethod')
			->with($this->equalTo('foo'))
			->will($this->returnValue($answer));

		$mock->foo();
	}

	/**
	 * Tests that generated mock classes will allow setting stubs to methods. This is delegated
	 * internally to the stubMapper
	 */
	public function testStubbableInterface()
	{
		$newClassName = __CLASS__ . '_TestClass8';
		$mockedClass = 'stdClass';

		$this->classGen->generate($newClassName, $mockedClass);

		$callRecorder = $this->getMock('Phake_CallRecorder_Recorder');
		$stubMapper = $this->getMock('Phake_Stubber_StubMapper');
		$mock = $this->classGen->instantiate($newClassName, $callRecorder, $stubMapper);

		$answer = $this->getMock('Phake_Stubber_StaticAnswer', array(), array(), '', FALSE);

		$stubMapper->expects($this->once())
			->method('mapStubToMethod')
			->with($this->equalTo($answer), $this->equalTo('foo'));

		$mock->__PHAKE_addAnswer($answer, 'foo');
	}
}
?>