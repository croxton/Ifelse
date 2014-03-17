#IfElse

* Author: [Mark Croxton](http://hallmark-design.co.uk/)

## Version 2.0.1

* Requires: [ExpressionEngine 2](http://expressionengine.com/)

## Description
Early parsing of advanced conditionals in EE templates.

## Installation

1. Create a folder called 'ifelse' (note: all lowercase) in ./system/expressionengine/third_party/
2. Copy the file pi.ifelse.php into this folder

## Examples

Use like this when you want to parse conditionals that wrap tags, *BEFORE* those tags are parsed. 
Only the tags inside matching conditionals will subsequently be parsed.

	{exp:ifelse parse="inward"}	
		{if member_id == '1' OR group_id == '2'}
			Admin content
		{if:elseif logged_in}
			Member content
		{if:else}
			Public content
		{/if}
	{/exp:ifelse}
	
### Parsing global variables and [Stash](https://github.com/croxton/Stash) variables

	{exp:ifelse parse="inward" parse_vars="yes"}	
		{if stash:my_var == "one"}
			One
		{if:elseif my_snippet_var == "two"}
			Two
		{/if}
	{/exp:ifelse}

### "Unsafe" conditional parsing

Use this method only when you want advanced conditionals to be parsed *AFTER* the tags IfElse wraps have been parsed. The `safe="no"` parameter disables certain checks and safety measures and can signigicantly reduce the overhead of EE's own advanced conditional parser (savings of up to 1/4 of total template execution time are possible). This works best when you have a large number of conditionals. 

The `protect=""` parameter can be used to specify special types of content to protect while parsing connditionals when using the "unsafe" method (currently 'javascript' and 'php').

	{exp:ifelse safe="no" protect="javascript|php"}	
		{if "{segment_1}" == "about"}
			About 
		{if:elseif "{segment_1}" == "services"}
			Services 
		{if:else}
			Default
		{/if}
	{/exp:ifelse}

Please be aware that when using `safe="no"` you may see parse errors with some conditionals that make use of un-quoted variables.

Avoid this syntax where possible:

	{if my_variable == "about"}...{/if}

Do this instead:

	{if "{my_variable}" == "about"}...{/if}


When evaluating variables containing numbers, it's safe to do this:

	{if {my_number} == 2}...{/if}


### Preserving {if no results}

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


## Nested conditionals

With parse="inward" is used with this plugin, advanced conditionals *inside* any plugin/module tag pairs wrapped by IfElse will not be evaluated; these will be left untouched for the parent tag to process.