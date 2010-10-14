<?php

$plugin_info = array(
  'pi_name' => 'IfElse',
  'pi_version' =>'1.0',
  'pi_author' =>'Mark Croxton',
  'pi_author_url' => 'http://www.hallmark-design.co.uk/',
  'pi_description' => 'If/Else advanced conditional logic early parsing',
  'pi_usage' => Ifelse::usage()
  );

class Ifelse {
	
	var $return_data = '';
	
	/** 
	 * Constructor
	 *
	 * Parses advanced conditionals in tagdata
	 *
	 * @access public
	 * @return void
	 */
	public function Ifelse() 
	{
		global $TMPL, $FNS, $SESS;
		
		// the variables we want to find
		$var = $TMPL->fetch_param('variables') ? $TMPL->fetch_param('variables') : '';
		
		// parse the parameter to get our variables
		$conditions = array();
		$var = explode('|', $var);
		
		foreach($var as $val)
		{
			$pair = explode('=', $val);
			
			// parse any session userdata vars that might have been used
			if (strncmp($pair[1], '{', 1) == 0)
			{
				$pair[1] = trim($pair[1], '{}');
				if (isset($SESS->userdata["{$pair[1]}"]) )
				{
					$pair[1] = $SESS->userdata[$pair[1]];
				}
			}
			$conditions[$pair[0]] = $pair[1];
		}
		
		$tagdata = $FNS->prep_conditionals($TMPL->tagdata, $conditions);

		// replace namespaced no_results with the real deal
		$this->return_data = str_replace(strtolower(__CLASS__).'_no_results', 'no_results', $tagdata);
	}

	// usage instructions
	public function usage() 
	{
  		ob_start();
?>
-------------------
HOW TO USE
-------------------

{exp:ifelse variables="member_id={member_id}|group_id={group_id}" parse="inward"}	
	{if member_id == '1' OR group_id == '2'}
		Privileged content
	{if:else}
		Public content
	{/if}
{/exp:ifelse}

{exp:ifelse variables="segment_1={segment_1}" parse="inward"}	
	{if segment_1 == 'news'}
		News page
	{if:else}
		Another page
	{/if}
{/exp:ifelse}

	<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}	
}