# Content Sharing Plugin

This plugin provides a shortcode in WordPress which can be used to share content from one page on another.  Multisite is supported, allowing content in one site to be shared on a page in another sub-site.

Since it is shortcode based, when content on the original page is updated, pages that are sharing its content are automatically updated as well.

A good use case for this would be to have content snippets managed in a central location but then shared to other pages as needed.

## Settings
*Settings screens only show up in multisite*.  The only setting value currently in use is Default Site ID, which is the site where the shortcode will pull content from unless otherwise specified within the shortcode itself. There are 2 locations where settings can be defined for this plugin.

* At the network level, under Settings -> Content Sharing. All sites will use this unless there is a site setting specified.
* At the site level, under Settings -> Content Sharing. This will override a network-level setting, allowing an individual site to specify a different default than the rest of the network.

## Usage
Place the following shortcode into a content area, replacing # with their respective ID's.

`[shared content=# (site=#) (strip_links=yes/no) (relative_urls=yes/no)]`

* `content` - Required parameter. Set to the Post ID of the content to retrieve.
* `site` - Only relevant if using Multisite. If using Multisite then this is an optional parameter as long as there is a site or network-wide default setting chosen onthe settings screens. Otherwise, this is a required parameter when using the shortcode. Set it to the ID of the site to pull content from. Specifying this parameter will override any site or network-wide default settings.
* `strip_links` - Defaults to no. If set to 'yes', then any links in the content will be stripped. The text of the link will be left, but the text will no longer be linked.
* `relative_urls` - Defaults to no. If set to 'yes', then any links will be changed to relative links. For example, if the original content links to "http://yoursite.com/my_content", then using this feature will cause it to just link to "/my_content"

## Examples

`[shared site=1 content=2]`

This will pull the content from Post ID 2, in Site ID 1

`[shared content=2]`

This will pull Post ID 2 from the default site. In order to locate the default site, it will first look for a default site specified at the site level. If no setting is found at the site level, then it will look for a default site specified at the network level.

## Notes
If the shortcode is unable to find content, it will not print an error. Instead it will print a zero length string.

In case the hierarchy of how the shortcode figures out what site to pull the content from is unclear, here is the priority list (top-most item wins when determining the Site ID to pull the content from):

- ID specified in shortcode
- ID specified in Site Settings
- ID specified in Network Settings
