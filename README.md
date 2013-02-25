Favorite Posts
====================

This is a simple yet another favorite post plugin.


Features
---------------
1. Gives a button to favorite a post.
2. Works only for logged in users.
3. Has a favorite post widget
4. Custom post type support

Usage
------------------

1. Insert `<?php if ( function_exists( 'wfp_button' ) ) wfp_button(); ?>` this code in your post page to show a favorite post link.
1. `$favorites = WeDevs_Favorite_Posts::init()->get_favorites();` - get favorite posts. Supports **3** parameters, `post_type`, `limit`, `offset`. The default `post_type` is `all`, for getting all post types.
1. Show favorite posts in a widget.
1. Use the shortcode `[favorite-post-btn]` for inserting the favorite post link. You can also pass a post id as a parameter. `[favorite-post-btn post_id="938"]`.
1. Use the shortcode `[favorite-post]` to display favorited posts. You can also pass these parameters: `user_id`, `count`, `post_type`. e.g. `[favorite-post user_id="1" count="5" post_type="all"]`


Author
----------------------------
[Tareq Hasan](http://tareq.wedevs.com)