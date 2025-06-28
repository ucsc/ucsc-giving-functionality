# UCSC Giving Functionality Plugin

[![Build and release](https://github.com/ucsc/ucsc-giving-functionality/actions/workflows/release.yml/badge.svg)](https://github.com/ucsc/ucsc-giving-functionality/actions/workflows/release.yml)

## Description

This plugin provides custom functionality to the [UC Santa Cruz Giving](https://giving.ucsc.edu) website by creating a custom post type for the Giving funds and linking each fund to its Giving Designation Form offsite.

Each fund can be designated a "Priority" fund or a "Standard" fund. Priority funds are described in their own Single Post template and link to their fund designation form from the post. Standard funds link straight to their fund designation form from their archive page or query loop display.

## Plugin requirements

- WordPress 6.5 or greater
- [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/)

## Details

This plugin implements the following:

- Creates a Custom Post Type for Giving Funds
- Creates Custom Taxonomies associated with with the new post type
    - Areas (of Impact)
    - (Fund) Themes
    - Keywords
- Creates templates for the new post type and taxonomies:
    - Single Funds Template
        - Single Template utilizes the [Block Bindings API](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/) that was introduced in WP6.5. This is used to bind the fund form URL to the "give" button on the template
    - Funds Archive Template
    - Area Archive Template
    - Fund Theme Archive Template
    - Fund Keyword Archive Template
    - Fund Type Archie Template
- Creates an ACF Field Group associated with the new post type for entering fund designation metadata, including:
    - a Fund Designation Code field on each single custom post
    - a global base url
- Creates a Block Variation of the search block scoped to the Fund post type
- Returns results separate from Global results page.

## Contributors

This plugin is maintained by the [University Advancement web team](https://advancement.ucsc.edu/about/the-team/communications-and-marketing/#:~:text=ngonza32%40ucsc.edu-,Digital%20Strategies,-Robert%20Allen%20Knight) in the campus [Communications](https://communications.ucsc.edu/) office. If you have any questions about this project, you can contact [Rob Knight](https://campusdirectory.ucsc.edu/cd_detail?uid=raknight), [Jason Chafin](https://campusdirectory.ucsc.edu/cd_detail?uid=jchafin), or [submit an issue](https://github.com/ucsc/ucsc-giving-functionality/issues) here on Github.
