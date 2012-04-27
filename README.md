#IfElse

* Author: [Mark Croxton](http://hallmark-design.co.uk/)

## Version 2.0.0 beta

* Requires: [ExpressionEngine 2](http://expressionengine.com/)

## Description
Early parsing of advanced conditionals in EE templates.

## Installation

1. Create a folder called 'ifelse' (note: all lowercase) in ./system/expressionengine/third_party/
2. Copy the file pi.ifelse.php into this folder

## Examples

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

This plugin will not parse advanced conditionals *inside* any plugin/module tag pairs; these will be left untouched for the parent tag to process.

This plugin cannot be nested inside itself. However, the if/else conditional tags themselves can be nested and only the matching condition will be parsed.