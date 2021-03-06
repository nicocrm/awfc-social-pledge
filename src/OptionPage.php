<?php
/**
 * OptionPa.php
 * Created By: nico
 * Created On: 11/28/2015
 */

namespace AWC\SocialPledge;


use F1\WPUtils\Admin\AdminPageHelper;

class OptionPage extends AdminPageHelper
{
    const AWC_SOCIALPLEDGE_SETTINGS = 'awc_social_pledge_settings';
    const OPTION_FACEBOOK_APPID = 'facebook_appid';
    const OPTION_TWITTER_SCREENNAME = 'twitter_screenname';
    const OPTION_TWITTER_CLIENTKEY = 'twitter_clientkey';
    const OPTION_TWITTER_CLIENTSECRET = 'twitter_clientsecret';
    const OPTION_TWITTER_ACCESSTOKEN = 'twitter_accesstoken';
    const OPTION_TWITTER_ACCESSTOKENSECRET = 'twitter_accesstokensecret';

    function __construct()
    {
        parent::__construct(self::AWC_SOCIALPLEDGE_SETTINGS, 'AWFC Social Pledge Settings');
        $this->addSetting(self::OPTION_FACEBOOK_APPID, 'Facebook App Id');
        $this->addSetting(self::OPTION_TWITTER_SCREENNAME, 'Twitter Screenname');
        $this->addSetting(self::OPTION_TWITTER_CLIENTKEY, 'Twitter Client ID');
        $this->addSetting(self::OPTION_TWITTER_CLIENTSECRET, 'Twitter Client Secret');
        $this->addSetting(self::OPTION_TWITTER_ACCESSTOKEN, 'Twitter Access Token', [$this, 'createTwitterOAuthBox']);
        //$this->addSetting(self::OPTION_TWITTER_ACCESSTOKENSECRET, 'Twitter Access Token Secret');
    }

    public function createTwitterOAuthBox($args)
    {
        $optionName = $args['name'];
        $option = $this->getOption($optionName);
        $isValid = false;
        if (isset($option)) {
            $api = new TwitterLogin();
            if (($twitterName = $api->validateToken())) {
                $token = "Twitter access token validated: $twitterName";
                $isValid = true;
            } else {
                $token = 'Twitter access token invalid - please Login with Twitter to reset it';
            }
        } else {
            $token = 'Twitter access token not set - please Login with Twitter';
        }
//        echo "<input type='hidden' id='$optionName' value='".esc_attr($option)."'
//            name='" . esc_attr(self::AWC_SOCIALPLEDGE_SETTINGS) . "[$optionName]'/>";
        echo '<p class="twitter-token-status">' . $token . '</p>';
        if (!$isValid) {
            echo "<button id='twitterLogin'>Login with Twitter</button>";
        } else {
            echo "<button id='twitterLogin'>Reset Twitter Login</button>";
        }
        echo '<div><i>Do not use an actively used Twitter account for this purpose.  It will be used for posting the ' .
            'images so that they can be shared in the social pledges.</i></div>';
    }

    public function registerOptionPage()
    {
        parent::registerOptionPage(AWC_SOCIAL_PLEDGE_PLUGIN_BASENAME);
    }

    public function onSanitizeOptions($options)
    {
        if (isset($options[self::OPTION_TWITTER_SCREENNAME])) {
            // remove @ from twitter name if they included it
            $options[self::OPTION_TWITTER_SCREENNAME] = str_replace('@', '', $options[self::OPTION_TWITTER_SCREENNAME]);
        }
        if (!isset($options[self::OPTION_TWITTER_ACCESSTOKEN])) {
            // reload this one from the database because it's not explicitly output with the page
            $options[self::OPTION_TWITTER_ACCESSTOKEN] = $this->getOption(self::OPTION_TWITTER_ACCESSTOKEN);
        }
        if (!isset($options[self::OPTION_TWITTER_ACCESSTOKENSECRET])) {
            // reload this one from the database because it's not explicitly output with the page
            $options[self::OPTION_TWITTER_ACCESSTOKENSECRET] = $this->getOption(self::OPTION_TWITTER_ACCESSTOKENSECRET);
        }
        return parent::onSanitizeOptions($options);
    }

    protected function onEnqueueScripts()
    {
        wp_enqueue_script('awc-social-pledge-admin', plugins_url('assets/js/admin.js', AWC_SOCIAL_PLEDGE_PLUGIN_BASENAME));
    }

    protected function onOutputSettingsPage()
    {
        echo '<table class="form-table"><tr>';
        echo '<th scope="row">Download campaign report</th>';
        echo '<td>';
        wp_dropdown_categories([
            'taxonomy' => SocialCampaignTaxonomy::TAXONOMY,
            'hierarchical' => 0,
            'name' => 'report_campaign',
            'value_field' => 'slug',
            'orderby' => 'name',
            'show_option_none' => 'Select a Campaign',
            'option_none_value' => '',
            'hide_empty' => false,
        ]);
        echo '</td></tr></table>';
    }

    public static function getAWCOption($optionName)
    {
        $opts = new OptionPage();
        return $opts->getOption($optionName);
    }

    public static function saveAWCOption($optionName, $value)
    {
        (new OptionPage())->saveOption($optionName, $value);
    }
}