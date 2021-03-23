# RemoveRedlinks extension for MediaWiki

This extension hides all redlinks and turns them into their static text.

Developed by FreedomFighterSparrow for Kol-Zchut Ltd.
Based on an original work by Chad Horohoe <innocentkiller@gmail.com>, 2009
Some fixes cherry-picked from alternative version developed by Malte Ahrholdt for https://www.nordicnames.de

## Configuration
`$wgRemoveRedLinksExemptGroups = [ 'a group we show red links', '2nd group' ];`

## License
GNU-GPL2+. See COPYING file.

## Changelog

- 2.1, 2021-03-10
  Reworked for MediaWiki 1.35, with some changes taken from an alternative version by Malte Ahrholdt,
  with plans to merge our changes to that published version at some point.
- 2.0, 2014-11-10
  - New: Use hook LinkEnd instead of LinkBegin, to save on duplicated checks
  - Bug: Diverge on parser cache using hook PageRenderingHash,
    so users won't get the (wrong) cached version

- 1.0 2010-03-25
  - Initial release by ^demon, later abandoned
