# Post Format Views

## Problem

Core post formats, as implemented, are incomplete and do not provide enough of a baseline for themes to extend.

## Concept

One of the primary failings of how post formats were implemented when introduced, was the lack of baseline templates showing how post formats could be leveraged by themes.

We would propose to extend the existing Template Hierarchy system to include a list of core-supplied template parts to be tied to specific post formats, all completely overrideable by the current theme.

In addition, we would propose to deprecate less-common default post formats, and possibly add the ability to introduce new ones while simultaneously giving core the ability to automagically handle newly-registered formats the same way as built-in ones. 


## Affected APIs
* Post Formats
* Themes (template parts and template hierarchy)

## Goals
* Create core-supported base template parts for each established post format
* Deprecate less-common post formats
* Open up extending post formats in an automagical way
