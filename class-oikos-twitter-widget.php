<?php
if (! class_exists('tmhOAuth')) {
	require 'tmhOAuth-master/tmhOAuth.php';
	require 'tmhOAuth-master/tmhUtilities.php';
}

class Oikos_TwitterWidget extends WP_Widget {
    /** constructor */
    function Oikos_TwitterWidget() {
        $options = array( 'description' => "Displays the Twitter widget" );
        parent::WP_Widget(false, $name = 'Oikos Twitter Widget', $options);	
    }

    // This calculates a relative time, e.g. "1 minute ago"
    private static function relativeTime($time)
    {   
        $second = 1;
        $minute = 60 * $second;
        $hour = 60 * $minute;
        $day = 24 * $hour;
        $month = 30 * $day;
        
        $delta = time() - $time;

        if ($delta < 1 * $minute)
        {
            return $delta == 1 ? "one second ago" : $delta . " seconds ago";
        }
        if ($delta < 2 * $minute)
        {
          return "a minute ago";
        }
        if ($delta < 45 * $minute)
        {
            return floor($delta / $minute) . " minutes ago";
        }
        if ($delta < 90 * $minute)
        {
          return "an hour ago";
        }
        if ($delta < 24 * $hour)
        {
          return floor($delta / $hour) . " hours ago";
        }
        if ($delta < 48 * $hour)
        {
          return "yesterday";
        }
        if ($delta < 30 * $day)
        {
            return floor($delta / $day) . " days ago";
        }
        if ($delta < 12 * $month)
        {
          $months = floor($delta / $day / 30);
          return $months <= 1 ? "one month ago" : $months . " months ago";
        }
        else
        {
            $years = floor($delta / $day / 365);
            return $years <= 1 ? "one year ago" : $years . " years ago";
        }
    }    

	// With thanks to The Danger Bees for the donkey work: http://dmblog.com/2011/08/how-to-use-tweet-entities/
	private static function linkify_tweet($raw_text, $tweet = NULL)
	{
		// first set output to the value we received when calling this function
		$output = $raw_text;

		// create xhtml safe text (mostly to be safe of ampersands)
		$output = htmlentities(html_entity_decode($raw_text, ENT_NOQUOTES, 'UTF-8'), ENT_NOQUOTES, 'UTF-8');

		// parse urls
		if ($tweet == NULL)
		{
			// for regular strings, just create <a> tags for each url
			$pattern        = '/([A-Za-z]+:\/\/[A-Za-z0-9-_]+\.[A-Za-z0-9-_:%&\?\/.=]+)/i';
			$replacement    = '<a href="${1}" rel="external">${1}</a>';
			$output         = preg_replace($pattern, $replacement, $output);
		} else {
			// for tweets, let's extract the urls from the entities object
			foreach ($tweet->entities->urls as $url)
			{
				$old_url        = $url->url;
				$expanded_url   = (empty($url->expanded_url))   ? $url->url : $url->expanded_url;
				$display_url    = (empty($url->display_url))    ? $url->url : $url->display_url;
				$replacement    = '<a href="'.$expanded_url.'" rel="external">'.$old_url.'</a>';
				$output         = str_replace($old_url, $replacement, $output);
			}

			// let's extract the hashtags from the entities object
			foreach ($tweet->entities->hashtags as $hashtags)
			{
				$hashtag        = '#'.$hashtags->text;
				$replacement    = '<a href="http://twitter.com/search?q=%23'.$hashtags->text.'" rel="external">'.$hashtag.'</a>';
				$output         = str_ireplace($hashtag, $replacement, $output);
			}

			// let's extract the usernames from the entities object
			foreach ($tweet->entities->user_mentions as $user_mentions)
			{
				$username       = '@'.$user_mentions->screen_name;
				$replacement    = '<a href="http://twitter.com/'.$user_mentions->screen_name.'" rel="external" title="'.$user_mentions->name.' on Twitter">'.$username.'</a>';
				$output         = str_ireplace($username, $replacement, $output);
			}

			// if we have media attached, let's extract those from the entities as well
			if (isset($tweet->entities->media))
			{
				foreach ($tweet->entities->media as $media)
				{
					$old_url        = $media->url;
					$replacement    = '<a href="'.$media->expanded_url.'" rel="external" class="twitter-media" data-media="'.$media->media_url.'">'.$media->display_url.'</a>';
					$output         = str_replace($old_url, $replacement, $output);
				}
			}
		}

		return $output;
	}


    public function get_tweets( $username, $count=3, $consumer_key, $consumer_secret, $user_token, $user_secret ) {

        if ( ! $tweets = get_transient( 'smf_tweets' ) ) {
            
            $tmhOAuth = new tmhOAuth(array(
                'consumer_key'    => $consumer_key,
                'consumer_secret' => $consumer_secret,
                'user_token'      => $user_token,
                'user_secret'     => $user_secret,
            ));

			/* Note that we get 20 Tweets by default here, as we don't include replies.  The way
			 * that this works is Twitter gets 'count' Tweets, and then filters out replies. So if you
			 * get 5 Tweets and the last 5 Tweets were all replies, then you'll get nothing.
			 */
			 $code = $tmhOAuth->request('GET', $tmhOAuth->url('1.1/statuses/user_timeline'), array(
                'screen_name' => $username,
                'count' => 20,
                'exclude_replies' => true ));
            if ($code == 200) {
                $tweets = json_decode($tmhOAuth->response['response']);
				// Now we slice the required number of tweets.
				$tweets = array_slice( $tweets, 0, $count );
			} else {
                $tweets = array();
            }

            set_transient('oikos_tweets', $tweets, 5 * 60);

        }

        return $tweets;
    }

    function widget($args, $instance) {
				
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
		$username = isset($instance['username']) ? $instance['username'] : '';
		$count = isset($instance['count']) ? $instance['count'] : 3;
		$consumer_key = isset($instance['consumer_key']) ? $instance['consumer_key'] : '';
		$consumer_secret = isset($instance['consumer_secret']) ? $instance['consumer_secret'] : '';
		$user_token = isset($instance['user_token']) ? $instance['user_token'] : '';
		$user_secret = isset($instance['user_secret']) ? $instance['user_secret'] : '';

		$twitter_url = 'http://twitter.com/' . $username;

		echo $before_widget;

	    if ( $title ) {
		    	// Construct the title
		    	$title_output = $before_title;
		    	
	    		$title_output .= sprintf('<a href="%s">%s</a>', $twitter_url, $title);

		    	$title_output .= $after_title;

		    	echo $title_output;
        }

        $tweets = self::get_tweets( $username, $count, $consumer_key, $consumer_secret, $user_token, $user_secret );
		
		while ($this_tweet = array_shift($tweets)) {
?>
			<div class="tweet-container">
				<a class="tweet-user-avatar" href="https://twitter.com/intent/user?user_id=<?php echo $this_tweet->user->id_str; ?>">
					<img width="32" height="32" src="<?php echo $this_tweet->user->profile_image_url; ?>" />
				</a>
				<p class="tweet-user-names">
					<a href="https://twitter.com/intent/user?user_id=<?php echo $this_tweet->user->id_str; ?>">
						<?php echo $this_tweet->user->name; ?>
					</a>
					<a href="https://twitter.com/intent/user?user_id=<?php echo $this_tweet->user->id_str; ?>">
						@<?php echo $this_tweet->user->screen_name; ?>
					</a>
				</p>
				<p class="tweet-time"><a href="http://twitter.com/<?php echo $this_tweet->user->screen_name; ?>/status/<?php echo $this_tweet->id_str; ?>"><?php echo self::relativeTime( strtotime( $this_tweet->created_at)); ?></a></p>
				<div class="tweet-text">
				<?php
					echo self::linkify_tweet( $this_tweet->text, $this_tweet );
				?>
				</div>
				<div class="tweet-intents">
					<a class="intent-reply" title="Reply to this Tweet" href="https://twitter.com/intent/tweet?in_reply_to=<?php echo $this_tweet->id_str; ?>">Reply</a>
					<a class="intent-retweet" title="Retweet this Tweet" href="https://twitter.com/intent/retweet?tweet_id=<?php echo $this_tweet->id_str; ?>">Re-tweet</a>
					<a class="intent-favorite" title="Favourite this Tweet" href="https://twitter.com/intent/favorite?tweet_id=<?php echo $this_tweet->id_str; ?>">Favourite</a>
				</div>
			</div>

	<?php	
		}
	?>
		<a href="https://twitter.com/<?php echo $username; ?>" class="twitter-follow-button" data-show-count="false" data-dnt="true">Follow @<?php echo $username; ?></a>
	<?php
		echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
        return $new_instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
						
        $title = esc_attr(isset($instance['title']) ? $instance['title'] : "Twitter");
		$username = isset($instance['username']) ? $instance['username'] : '';
		$count = isset($instance['count']) ? $instance['count'] : 3;
		$consumer_key = isset($instance['consumer_key']) ? $instance['consumer_key'] : '';
		$consumer_secret = isset($instance['consumer_secret']) ? $instance['consumer_secret'] : '';
		$user_token = isset($instance['user_token']) ? $instance['user_token'] : '';
		$user_secret = isset($instance['user_secret']) ? $instance['user_secret'] : '';
		?>
            <p>This widget displays the latest Tweet</p>
			<p>
                <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
            </p>
			<p>
                <label for="<?php echo $this->get_field_id('username'); ?>">Username:</label>
                <input class="widefat" id="<?php echo $this->get_field_id('username'); ?>" name="<?php echo $this->get_field_name('username'); ?>" type="text" value="<?php echo $username; ?>" />
            </p>
			<p>
                <label for="<?php echo $this->get_field_id('count'); ?>">Number of tweets:</label>
                <select id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>">
					<?php
						for ($i=1; $i<=20; $i++) {
							printf('<option value="%d" %s>%d</option>', $i, selected($i, $count, false), $i);
						} 
					?>
				</select>
            </p>
			<p>
                <label for="<?php echo $this->get_field_id('consumer_key'); ?>">Consumer Key:</label>
                <input class="widefat" id="<?php echo $this->get_field_id('consumer_key'); ?>" name="<?php echo $this->get_field_name('consumer_key'); ?>" type="text" value="<?php echo $consumer_key; ?>" />
            </p>
			<p>
                <label for="<?php echo $this->get_field_id('consumer_secret'); ?>">Consumer Secret:</label>
                <input class="widefat" id="<?php echo $this->get_field_id('consumer_secret'); ?>" name="<?php echo $this->get_field_name('consumer_secret'); ?>" type="text" value="<?php echo $consumer_secret; ?>" />
            </p>
			<p>
                <label for="<?php echo $this->get_field_id('user_token'); ?>">User Token:</label>
                <input class="widefat" id="<?php echo $this->get_field_id('user_token'); ?>" name="<?php echo $this->get_field_name('user_token'); ?>" type="text" value="<?php echo $user_token; ?>" />
            </p>
			<p>
                <label for="<?php echo $this->get_field_id('user_secret'); ?>">User Secret:</label>
                <input class="widefat" id="<?php echo $this->get_field_id('user_secret'); ?>" name="<?php echo $this->get_field_name('user_secret'); ?>" type="text" value="<?php echo $user_secret; ?>" />
            </p>
        
				<?php 
    }
}

add_action('widgets_init', create_function('', 'return register_widget("Oikos_TwitterWidget");'));
