<?php
Yii::import('application.commands.DownloadCommand');
require_once 'vfsStream/vfsStream.php';

class DownloadCommandTest extends CDbTestCase {
	public function setUp() {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('exampleDir'));
    }
	
	public function testShellCommand() {
		 $commandName='Download';
		 $CCRunner=new CConsoleCommandRunner();
					
		 $download = new DownloadCommand($commandName,$CCRunner);
		// $this->assertTrue($download->run(array()));
	}
}