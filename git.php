<?php

/**
 * @author      Initial coding and development - Neal Horman - http://www.wanlink.com
 *
 * @copyright   Copyright (c) 2018 by Neal Horman. All Rights Reserved.
 *
 * @license	Redistribution and use in source and binary forms, with or without
 * @license	modification, are permitted provided that the following conditions
 * @license	are met:
 * @license	1. Redistributions of source code must retain the above copyright
 * @license	   notice, this list of conditions and the following disclaimer.
 * @license	2. Redistributions in binary form must reproduce the above copyright
 * @license	   notice, this list of conditions and the following disclaimer in the
 * @license	   documentation and/or other materials provided with the distribution.
 * @license	3. Neither the name Neal Horman nor the names of any contributors
 * @license	   may be used to endorse or promote products derived from this software
 * @license	   without specific prior written permission.
 * @license
 * @license THIS SOFTWARE IS PROVIDED BY NEAL HORMAN AND ANY CONTRIBUTORS ``AS IS'' AND
 * @license ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * @license IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * @license ARE DISCLAIMED.  IN NO EVENT SHALL NEAL HORMAN OR ANY CONTRIBUTORS BE LIABLE
 * @license FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * @license DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * @license OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * @license HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * @license LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * @license OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * @license SUCH DAMAGE.
 *
 */

class Git
{
	public function __construct($dir = '')
	{
		if(!isset($dir) || $dir == "")
			$dir='.';
		$this->dir($dir);
	}

	public function cmd($cmd)
	{
		ob_start();
		system($cmd);
		$obstr = ob_get_contents();
		ob_end_clean();

		return $obstr;
	}

	public function dir($dir)
	{
		$this->mDir = $dir;
		$v = $this->cmd('cd '.$this->mDir.'; git config --get remote.origin.url');
		//$this->mRepo = $this->cmd('cd '.$this->mDir."; git remote -v | grep push | awk '{print $2}'");
		if(isset($v) && $v != "")
			$this->mRepo = explode(':', trim($v))[2];
	}

	public function last()
	{
		$fieldKeys = array('hashShort', 'hashLong', 'author', 'time');
		$fieldVals = explode('|', $this->cmd('cd '.$this->mDir.'; git log --pretty="format:%h|%H|%cn|%ct" -1'));

		$info = array_combine($fieldKeys, $fieldVals);
		$info['log'] = $this->cmd('cd '.$this->mDir.'; git log --pretty="format:%s" -1');
		$info['dir'] = $this->mDir;
		if(isset($this->mRepo))
			$info['repo'] = $this->mRepo;

		$x = explode("\n", trim($this->cmd('cd '.$this->mDir.'; git log --stat --oneline -1')));
		//$info['log'] = $x[0];
		array_shift($x);
		$info['history'] = implode("\n",$x);
		/*
		$info['history'] = trim($this->cmd('cd '.$this->mDir.'; git log --stat --oneline --color=always -1'));
		$old = ["\e[m", "\e[33m", "\e[32m", "\e[31m"];
		$new = ['</font>', '<font color="yellow">', '<font color="green">', '<font color="red">'];
		$info['colored'] = str_replace($old, $new, $info['history']);
		*/

		return $info;
	}

	private $mDir;
}

?>
