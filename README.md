# Watt, by CASH music
Watt is a new publication from CASH Music that asks and answers questions about music, industry, and the role of art in our world.  

<img src="https://static-cashmusic.netdna-ssl.com/www/img/constant/texture/watt-home.jpg" width="100%" />

We've built Watt using widely used technologies like JSON, markdown, and
mustache. The goal was to build a system that was lean (like a Jeckyll) but also
powerful enough to handle tagging, serve content over an open JSON API, and
provide format RSS feeds, etc.


## Quick docs: adding stories to Watt

### Pay attention:
**TO VIEW LOCAL CHANGES YOU MAY NEED TO DELETE THE FOLLOWING GENERATED FILES BEFORE REFRESHING YOUR BROWER**

_generated_authors.json
_generated_tags.json
_generated_work.json


### Index Page

  1) Settings

        Ouputs to the Watt homepage. This file is a series of arrays (lists) that outline which articles to pull through into your chosen Index template.

        Located in archive > settings.json

        /**********************************************************************/

        {
        	"featured_work":["whyamidoingthistomyself"],
        	"secondary_work":["maggievail","meredithgraves","letterfromclaire","impactcolinmeloy"],
        	"tertiary_work":[],
        	"quaternary_work":[],
        	"featured_authors":[],
        	"featured_tags":["impact","culture", "community", "sustainability"],
        	"featured_video":[],
        	"template":"index"
        }

        /**********************************************************************/


  2) Index templates

        1)  index -  Generic Index Structure - ( featured / secondary / tertiary / quarternary )
        2)  index-first - First used template - ( statement feature / secondary array / tertiary_work array )
        3)  index-statement - Generic Index Structure w/ featured statement - ( featured statement / secondary / tertiary / quarternary )
        4)  index-feat-video - Generic Index Structure w/ featured video - ( featured video )
        5)  index-leftalign-feature - Generic Index Structure w/ left aligned featured story - ( featured / secondary / tertiary / quarternary )
        6)  index-rightalign-feature - Generic Index Structure w/ right aligned featured story - ( featured / secondary / tertiary / quarternary )
        7)  index-no-feature - Generic Index Structure w/o featured story - ( secondary / tertiary / quarternary )


### Tag pages

File should be named same as the tag item of the article

/******************************************************************************/

  Output location :- watt.cashmusic.org/tag/NAMEOFTAG

  {
	   "title":"Impact of music",
	    "description":"Music impacts nearly every aspect of our lives. We ask folks about how, why, and how best to support the people that make it.",
	     "featured_work":[
		     "impactcolinmeloy"
	]
  }

"featured_work"  = top featured article of tag page. (outputted in secondary article style)

/******************************************************************************/



### Creating An Article

Articles are a creation of three files. author.json / content.md / article.json

General Information

*Image Locations*

All images should be located on the CDN

Contributor icons :
https://static-cashmusic.netdna-ssl.com/www/img/contributor

Article images :
https://static-cashmusic.netdna-ssl.com/www/img/article/


*Creative Commons License types*

PD  -  (Public Domain  -  https://wiki.creativecommons.org/wiki/Public_domain)
CC-0  -  (Creative Commons: Public Domain Dedication  -  https://creativecommons.org/publicdomain/zero/1.0/)
CC-BY  -  (Creative Commons: Attribution  -  https://creativecommons.org/licenses/by/4.0/)
CC-BY-NC  -  (Creative Commons: Attribution-NonCommercial  -  https://creativecommons.org/licenses/by-nc/4.0)
CC-BY-ND  -  (Creative Commons: Attribution-NoDerivs  -  https://creativecommons.org/licenses/by-nd/4.0)
CC-BY-SA  -  (Creative Commons: Attribution-ShareAlike  -  https://creativecommons.org/licenses/by-sa/4.0)
CC-BY-NC-ND  -  (Creative Commons: Attribution-NonCommercial-NoDerivs  -  https://creativecommons.org/licenses/by-nc-nd/4.0)
CC-BY-NC-SA  -  (Creative Commons: Attribution-NonCommercial-ShareAlike  -  https://creativecommons.org/licenses/by-nc-sa/4.0)


*Templates*


*Step One - Creating a new Author*

New authors files should be added in content > authors

This is a basic .json structure

/******************************************************************************/

    {
      "id":"maggievail",
      "name":"Maggie Vail",
      "byline":"Executive Director, CASH Music",
      "links":[
        {
          "title":"@magicbeans",
          "url":"https://twitter.com/magicbeans"
        }
      ],
      "photo":{
        "url":"https://pbs.twimg.com/profile_images/715427964838498304/h2xhzfZk_400x400.jpg",
        "license":"",
        "credits":[
          {
            "username":"@polytropos",
            "user_url":"https://www.flickr.com/photos/polytropos/1507925081/"
          }
        ]
      }
    }

/******************************************************************************/

*Step Two - Creating a new article reference*

New authors files should be added in content > work

Files should be named to give an article memorable context.
This is a basic .json structure

/******************************************************************************/

    {
      "id":"maggievail",
      "author_id":"maggievail",
      "release_id":"first",
      "title":"Welcome to Watt",
      "description":"I believe deeply that being a musician is an important and valid vocation. Watt is here to help make it easier to be one.",
      "type":"writing",
      "date":"June 2, 2016",
      "license":"CC-BY",
      "assets":[
        {
          "type":"image",
          "license":"CC-BY",
          "url":"https://static-cashmusic.netdna-ssl.com/www/img/article/mv.gif",
          "credits":""
        }
      ],
      "tags":[
        "culture",
        "community",
        "sustainability",
        "independence"
      ],
      "template":"twocol-twothirds-hero",
      "template_blend":"",
      "template_bg_style":"bg-cover",
      "template_styles":"#maggievail{background-color:#3c3240;}",
      "template_additional":"light"
    }
