<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
  'pi_name' => 'IfElse',
  'pi_version' =>'2.0.0',
  'pi_author' =>'Mark Croxton',
  'pi_author_url' => 'http://www.hallmark-design.co.uk/',
  'pi_description' => 'Early parsing of if/else advanced conditionals (EE 2.x)',
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
		$this->EE =& get_instance();
		
		// record if PHP is enabled for this template
		$parse_php = $this->EE->TMPL->parse_php;

		// optionally parse global variables and segments
		if ((bool) preg_match('/1|on|yes|y/i', $this->EE->TMPL->fetch_param('parse_vars', 'no')))
		{
			// insert segment vars into the globals array
			for ($i = 1; $i < 10; $i++)
			{
				$this->EE->config->_global_vars['segment_'.$i] = $this->EE->uri->segment($i);
			}
			
			// stash vars {stash:var}
			if (isset($this->EE->session->cache['stash']))
			{
				if (count($this->EE->session->cache['stash']) > 0)
				{
					foreach($this->EE->session->cache['stash'] as $key => $val)
					{
						$this->EE->config->_global_vars['stash:'.$key] = $val;
					}
				}
			}
			
			// replace into template
			foreach ($this->EE->config->_global_vars as $key => $val)
			{
				$this->EE->TMPL->tagdata = str_replace(LD.$key.RD, $val, $this->EE->TMPL->tagdata);
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
		$TMPL2 = $this->EE->TMPL;
		unset($this->EE->TMPL);
		
		// initialise a new object instance to do the heavy lifting
		$this->EE->TMPL = new EE_Template();
		$this->EE->TMPL->start_microtime = $TMPL2->start_microtime;
		$this->EE->TMPL->template = $TMPL2->tagdata;
		$this->EE->TMPL->tag_data	= array();
		$this->EE->TMPL->var_single = array();
		$this->EE->TMPL->var_cond	= array();
		$this->EE->TMPL->var_pair	= array();
		$this->EE->TMPL->plugins = $TMPL2->plugins;
		$this->EE->TMPL->modules = $TMPL2->modules;
		
		 // create a markers in the template for each tag (without actually parsing them)
		$this->EE->TMPL->parse_tags();
		
		 // parse advanced conditionals
		$this->EE->TMPL->template = $TMPL2->advanced_conditionals($this->EE->TMPL->template);
		
		// reset the loop counter
		$this->EE->TMPL->loop_count = 0;
		
		// copy template data back to our temporary clone	
		$TMPL2->tagdata = $this->EE->TMPL->template;
		$TMPL2->log = array_merge($TMPL2->log, $this->EE->TMPL->log);
		
		// now loop through and find the original chunk for each marker in the template
		for ($i = 0, $ctd = count($this->EE->TMPL->tag_data); $i < $ctd; $i++)
		{	
			// replace the chunk into the template
			$TMPL2->tagdata = str_replace('M'.$i.$this->EE->TMPL->marker, $this->EE->TMPL->tag_data[$i]['chunk'], $TMPL2->tagdata);
		}
		
		// restore the original object
		foreach (get_object_vars($TMPL2) as $key => $value)
		{
			$this->EE->TMPL->$key = $value;
		}
		
		$this->EE->TMPL = $TMPL2;	
		unset($TMPL2);
		
		// restore no_results conditionals
		$this->EE->TMPL->tagdata = str_replace('{no_results}', '{if no_results}', $this->EE->TMPL->tagdata);
		$this->EE->TMPL->tagdata = str_replace('{/no_results}', '{/if}', $this->EE->TMPL->tagdata);
		
		// restore original parse_php flag for this template
		$this->EE->TMPL->parse_php = $parse_php;
		
		// return
		$this->return_data = $this->EE->TMPL->tagdata;
	}

	// usage instructions
	public function usage() 
	{
  		ob_start();
?>
-------------------
HOW TO USE
-------------------

{exp:ifelse parse="inward"}	
	{if member_id == '1' OR group_id == '2'}
		Admin content
	{if:elseif logged_in}
		Member content
	{if:else}
		Public content
	{/if}
{/exp:ifelse}

To preserve {if no results} conditionals inside nested tags, wrap your 'no results' content with {no_results}{/no_results}. Example:

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

Some notes about nesting:
This plugin will not parse advanced conditionals *inside* any nested plugin/module tags; these will be left untouched for the parent tag to process.

This plugin cannot currently be nested inside itself due to a flaw in the way the EE template parser works. However, the if/else conditional tags themselves can be nested.

	<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}	
}

/* End of file pi.ifelse.php */ 
/* Location: ./system/expressionengine/third_party/ifelse/pi.ifelse.php */