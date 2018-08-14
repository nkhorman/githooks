#!/usr/bin/env php
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

/*
 * some great info;
 *	https://help.github.com/enterprise/2.14/admin/guides/developer-workflow/creating-a-pre-receive-hook-script/
 *
 */

require 'slack.php';
require 'git.php';

$slack = [ 'mgr' => new Slack('none'), 'channel' => '' ];
$git = new Git();
$commit = [];
$args = $_SERVER['argv'];

$slackRepos = [];
require 'slackrepos.php'; // this must be after $slackRepos = [];

function commitSend($commit, $slack)
{
	$slackMsg = new SlackMsg;
	$ar = [
		'commitTitle' => $commit['commitTitle'],
		'commitAuthor' => $commit['author'],
		'commitDate' => $commit['time'],
		'commitLog' => $commit['log'],
		'commitHistory' => $commit['history'],
		];
	if(isset($commit['commitUrl']))
		$ar['commitUrl'] = $commit['commitUrl'];
	$slackMsg->commit($ar);

	$slackResult = $slack['mgr']->Send($slackMsg, $slack['channel']);
}

function slackUpdate(&$slack, $repos, $repo)
{
	$x = @$repos[$repo];
	if(!isset($x))
		$x = @$repos['default'];

	if(isset($x))
	{
		if(isset($x['url']))
			$slack['mgr']->url($x['url']);
		if(isset($x['channel']))
			$slack['channel'] = $x['channel'];
	}
}

array_shift($args);
if(isset($args) && is_array($args) && count($args))
{
	for($i=0,$q=count($args); $i<$q; $i++)
	{
		$v = $args[$i];
		switch($v)
		{
			case '--send':
				/*
				//$commit['commitUrl'] = $commit['url'].$commit['project'].'/commit/'.$commit['hashLong'];
				//$commit['commitTitle'] = 'Commit <'.$commit['commitUrl'].'|'.$commit['hashShort'].'>';
				*/
				while($f = fgets(STDIN))
				{
					$ar = explode(" ", $f);
					// [0] = old revision
					// [1] = new revision
					// [2] = ref name
					//echo "revold: ".$ar[0]." revnew: ".$ar[1]." ref: ".$ar[2];
					$revs = $git->revs($ar[0], $ar[1]);
					$commitstart = $commit;
					foreach($revs as $rev)
					{
						$commit = $commitstart;
						$commit = array_replace($commit, $git->rev($rev));
						$commit['commitTitle'] = 'Commit '.$commit['hashShort'];
						if(isset($commit['repo']) && $commit['repo'] != "")
							$commit['commitTitle'] = $commit['commitTitle'].' --> '.$commit['repo'];
						commitSend($commit, $slack);
					}
				}
				break;
			case '--show':
				if(count($commit) == 0)
					$commit = array_replace($commit, $git->rev());
				print_r($commit);
				break;
			case '--channel': $slackChannel = $args[++$i];; break;
			case '--dir':
				$git->dir($args[++$i]);
				$commit = array_replace($commit, $git->rev());
				break;
			case '--repo':
				$commit['repo'] = $args[++$i];
				slackUpdate($slack, $slackRepos, $commit['repo']);
				break;
		}
	}
}

?>
