<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
  'pi_name' => 'IfElse',
  'pi_version' =>'2.0.1',
  'pi_author' =>'Mark Croxton',
  'pi_author_url' => 'http://www.hallmark-design.co.uk/',
  'pi_description' => 'Early parsing of if/else advanced conditionals',
  'pi_usage' => Ifelse::usage()
  );

class Ifelse {
	
	public $return_data = '';
	
	/** 
	 * Constructor
	 *
	 * Parses advanced conditionals in template tagdata
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() 
	{
		// register parameters
		$parse_vars = (bool) preg_match('/1|on|yes|y/i', ee()->TMPL->fetch_param('parse_vars', 'no')); // default: no
		$safe 		= (bool) preg_match('/1|on|yes|y/i', ee()->TMPL->fetch_param('safe', 'yes')); // default: yes
		
		if ($protect = ee()->TMPL->fetch_param('protect')) 
		{
			$protect = explode('|', $protect); // e.g. protect="javascript|php"
		}
		else
		{
			$protect = array();
		}

		// record if PHP is enabled for this template
		$parse_php = ee()->TMPL->parse_php;

		// optionally parse global variables and segments
		if ($parse_vars)
		{
			// insert segment vars into the globals array
			for ($i = 1; $i < 10; $i++)
			{
				ee()->config->_global_vars['segment_'.$i] = ee()->uri->segment($i);
			}
			
			// stash vars {stash:var}
			if (isset(ee()->session->cache['stash']))
			{
				if (count(ee()->session->cache['stash']) > 0)
				{
					foreach(ee()->session->cache['stash'] as $key => $val)
					{
						if (is_string($val))
						{
							ee()->config->_global_vars['stash:'.$key] = $val;
						}
					}
				}
			}
			
			// replace into template
			foreach (ee()->config->_global_vars as $key => $val)
			{
				if (is_string($val))
				{
					ee()->TMPL->tagdata = str_replace(LD.$key.RD, $val, ee()->TMPL->tagdata);
				}
			}
		}

		/*
		================================================================
    	Create a new instance of the Template object to parse the tags 
		inside our tagdata. We'll replace the tags with markers, then 
		parse advanced conditionals first instead of the tags
		================================================================
		*/
		
		// clone then unset the original Template object
		$TMPL2 = ee()->TMPL;
		unset(ee()->TMPL);
		
		// initialise a new object instance to do the heavy lifting
		ee()->TMPL = new EE_Template();
		ee()->TMPL->start_microtime = $TMPL2->start_microtime;
		ee()->TMPL->template = $TMPL2->tagdata;
		ee()->TMPL->tag_data = array();
		ee()->TMPL->var_single = array();
		ee()->TMPL->var_cond = array();
		ee()->TMPL->var_pair = array();
		ee()->TMPL->plugins = $TMPL2->plugins;
		ee()->TMPL->modules = $TMPL2->modules;
		ee()->TMPL->module_data = $TMPL2->module_data;
		
		// create a markers in the template for each tag (without actually parsing them)
		ee()->TMPL->parse_tags();
		
		// parse advanced conditionals
		if ($safe)
		{
			if ( ! isset($TMPL2->layout_conditionals))
            {
                $TMPL2->layout_conditionals = array();
            }
			ee()->TMPL->template = $TMPL2->advanced_conditionals(ee()->TMPL->template);
		}
		else
		{
			ee()->TMPL->template = $this->_crude_parse_conditionals(ee()->TMPL->template, $TMPL2, $protect);
		}
		
		// reset the loop counter
		ee()->TMPL->loop_count = 0;
		
		// copy template data back to our temporary clone	
		$TMPL2->tagdata = ee()->TMPL->template;
		$TMPL2->log = array_merge($TMPL2->log, ee()->TMPL->log);
		
		// now loop through and find the original chunk for each marker in the template
		for ($i = 0, $ctd = count(ee()->TMPL->tag_data); $i < $ctd; $i++)
		{	
			// replace the chunk into the template
			$TMPL2->tagdata = str_replace('M'.$i.ee()->TMPL->marker, ee()->TMPL->tag_data[$i]['chunk'], $TMPL2->tagdata);
		}
		
		// restore the original object
		foreach (get_object_vars($TMPL2) as $key => $value)
		{
			ee()->TMPL->$key = $value;
		}
		
		ee()->TMPL = $TMPL2;	
		unset($TMPL2);
		
		// restore no_results conditionals
		ee()->TMPL->tagdata = str_replace('{no_results}', '{if no_results}', ee()->TMPL->tagdata);
		ee()->TMPL->tagdata = str_replace('{/no_results}', '{/if}', ee()->TMPL->tagdata);
		
		// restore original parse_php flag for this template
		ee()->TMPL->parse_php = $parse_php;
		
		// return
		$this->return_data = ee()->TMPL->tagdata;
	}

	// --------------------------------------------------------------------

	/**
	 * Crude parse conditionals
	 *
	 * A quicker alternative to the Template Parser's advanced_conditionals() 
	 * which does without the prepping and some safety checks.
	 *
	 * @param	string
	 * @param	object The TMPL object instance
	 * @param	array Content to protect while conditonals are parsed
	 * @return	string
	 */
	protected function _crude_parse_conditionals($str, $tmpl, $protect)
	{
		$protect = array_flip($protect);

		// Protect already existing unparsed PHP?
		if(isset($protect['php']))
		{
			$opener = unique_marker('tmpl_php_open');
			$closer = unique_marker('tmpl_php_close');
			$str = str_replace(array('<?', '?'.'>'),
								array($opener.'?', '?'.$closer),
								$str);
		}

		// Protect <script> tags?
		if(isset($protect['javascript']))
		{
			$protected = array();
			$front_protect = unique_marker('tmpl_script_open');
			$back_protect  = unique_marker('tmpl_script_close');

			if ($this->protect_javascript !== FALSE &&
				stristr($str, '<script') &&
				preg_match_all("/<script.*?".">.*?<\/script>/is", $str, $matches))
			{
				for($i=0, $s=count($matches[0]); $i < $s; ++$i)
				{
					$protected[$front_protect.$i.$back_protect] = $matches[0][$i];
				}

				$str = str_replace(array_values($protected), array_keys($protected), $str);
			}
		}

		// convert if/else to PHP and evaluate (urgh)
		$str = str_replace(array(LD.'/if'.RD, LD.'if:else'.RD), array('<?php endif; ?'.'>','<?php else : ?'.'>'), $str);

		if (strpos($str, LD.'if') !== FALSE)
		{
			$str = preg_replace("/".preg_quote(LD)."((if:(else))*if)\s+(.*?)".preg_quote(RD)."/s", '<?php \\3if(\\4) : ?'.'>', $str);
		}
		
		$str = $tmpl->parse_template_php($str);

		// Unprotect <script> tags
		if(isset($protect['javascript']))
		{
			if (count($protected) > 0)
			{
				$str = str_replace(array_keys($protected), array_values($protected), $str);
			}
		}

		// Unprotect already existing unparsed PHP
		if(isset($protect['php']))
		{
			$str = str_replace(array($opener.'?', '?'.$closer),
								array('<'.'?', '?'.'>'),
								$str);
		}

		return $str;
	}

	// usage instructions
	public function usage() 
	{
  		ob_start();
?>
-------------------
HOW TO USE
-------------------

1. With parse="inward"

Use this form when you want to parse conditionals that wrap tags, *BEFORE* those tags are parsed. 
Only the tags inside matching conditionals will subsequently be parsed.

{exp:ifelse parse="inward"}	
	{if segment_1 == 'about'}
		About - complex tags here
	{if:elseif segment_1 == "services"}
		Services - moere complex tags here
	{if:else}
		Default
	{/if}
{/exp:ifelse}

To preserve {if no results} conditionals inside nested tags, wrap your 'no results' content with {no_results}{/no_results}. 

Example:

{exp:ifelse parse="inward"}	
	{if segment_1 == 'news' AND segment_2 == 'category'}
		News category page
		{exp:channel:entries channel="news"}
			{no_results} 
				No results 
			{/no_results}
		{/exp:channel:entries}
	{if:elseif segment_1 == 'news' AND segment_2 == ''}
	 	News landing page
	{if:else}
		News story page
	{/if}
{/exp:ifelse}


2. With safe="no"

Use this when you want advanced conditionals to be parsed *AFTER* the tags it has wrapped have been parsed. 
The safe="no" parameter disables certain checks and safety measures and can signigicantly reduce the overhead 
of EE's own advanced conditional parser. This works best when you have a large number of conditionals, and be 
aware that you may see parse errors with some types of conditionals, so only use this when you really need 
to squeeze additional performance from your templates. Savings of up to 1/4 of total execution time are possible.
The protect="" parameter can be used to specify types of content to protect while parsing connditionals.

{exp:ifelse safe="no" protect="javascript|php"}	
	{if "{segment_1}" == "about"}
		About 
	{if:elseif "{segment_1}" == "services"}
		Services 
	{if:else}
		Default
	{/if}
{/exp:ifelse}


Some notes about nesting:
This plugin will not parse advanced conditionals *inside* any nested plugin/module tags; these will be left untouched for the parent tag to process.

	<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}	
}

/* End of file pi.ifelse.php */ 
/* Location: ./system/expressionengine/third_party/ifelse/pi.ifelse.php */