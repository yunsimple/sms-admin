<?php


namespace app\common\controller;


use app\common\model\CountryModel;
use app\common\model\PhoneModel;
use think\Controller;
use think\facade\Request;

class SitemapMytempsmsController extends Controller
{

    public $website = 'https://sms.com/';
    public function create($website){
        $this->website = 'https://' . $website . '/';
        $sitemap = [];
        $sitemap_data = [
            [
                'url' => $this->website,
                'changefreq' => 'daily',
                'score' => '1.0'
            ],[
                'url' => $this->website . 'country.html',
                'changefreq' => 'daily',
                'score' => '0.9'
            ],[
                'url' => $this->website . 'mail.html',
                'changefreq' => 'daily',
                'score' => '0.9'
            ],[
                'url' => $this->website . 'gat-phone-number.html',
                'changefreq' => 'daily',
                'score' => '0.9'
            ],[
                'url' => $this->website . 'foreign-phone-number.html',
                'changefreq' => 'daily',
                'score' => '0.9'
            ]
        ];
        $sitemap_data = $this->insertData($sitemap_data, $this->generateCountryPhoneNumber());
        $sitemap_data = $this->insertData($sitemap_data, $this->generateArticle([25,22,23]));
        $sitemap_data = $this->insertData($sitemap_data, $this->generateUrlNumber($this->website . 'phone-number/page/[number].html', 56));
        $sitemap_data = $this->insertData($sitemap_data, $this->generateUrlNumber($this->website . 'china-phone-number/page/[number].html', 40));
        $sitemap_data = $this->insertData($sitemap_data, $this->generateUrlNumber($this->website . 'uk-phone-number/page/[number].html', 5));
        $sitemap_data = $this->insertData($sitemap_data, $this->generateUrlNumber($this->website . 'usa-phone-number/page/[number].html', 8));
        $sitemap_data = $this->insertData($sitemap_data, $this->generateUrlNumber($this->website . 'canada-phone-number/page/[number].html', 2));
        $sitemap_data = $this->insertData($sitemap_data, $this->generatePhonePage());
        for ($i = 0; $i < count($sitemap_data); $i++){
            $sub_sitemap = $this->sitemapUrl($sitemap_data[$i]['url'], $sitemap_data[$i]['changefreq'], $sitemap_data[$i]['score']);
            array_push($sitemap, $sub_sitemap);
        }
        $result = $this->sitemapDom($website, $sitemap);
        return $result;
    }

    public function insertData($sitemap_data, $new_data){
        foreach ($new_data as $value){
            array_push($sitemap_data, $value);
        }
        return $sitemap_data;
    }

    //https://sms.com/china-phone-number.html 国家页列表数据
    //https://sms.com/phone-number/page/2.html
    //https://sms.com/china-phone-number/page/2.html
    //https://sms.com/china-phone-number/verification-code-17096235871.html
    //https://sms.com/china-phone-number/17096235871/2.html
    public function generateCountryPhoneNumber(){
        $country_all_info = (new CountryModel())->getAllCountry();
        $country_phone_number = [];
        for ($i = 0; $i < count($country_all_info); $i++){
            $country_phone_number[$i]['url'] = $this->website . strtolower($country_all_info[$i]['en_title']).'-phone-number.html';
            $country_phone_number[$i]['changefreq'] = 'daily';
            $country_phone_number[$i]['score'] = '0.9';
        }
        return $country_phone_number;
    }

    //生成所有号码页和page页
    public function generatePhonePage(){
        $phone_info = (new PhoneModel())->getAllPhone();
        $data = [];
        $sub_datas = [];
        for ($i = 0; $i < count($phone_info); $i++){
            $url = $this->website . strtolower($phone_info[$i]['country']['en_title']).'-phone-number/verification-code-'.$phone_info[$i]['phone_num'].'.html';
            $data[$i]['url'] = $url;
            $data[$i]['changefreq'] = 'daily';
            $data[$i]['score'] = '0.9';
            if ($phone_info[$i]['warehouse']['message_save'] == 1){
                $sub_data = $this->generateUrlNumber(substr_replace($url, '/[number]', -5, 0), 10);
                $sub_datas = $this->insertData($sub_datas, $sub_data);
            }
        }
        $data = $this->insertData($data, $sub_datas);
        return $data;
    }

    //生成文章页
    public function generateArticle($id = []){
        $data[0]['url'] = $this->website . 'article.html';
        $data[0]['changefreq'] = 'daily';
        $data[0]['score'] = '0.9';
        for ($i = 1; $i < count($id) + 1; $i++){
            $data[$i]['url'] = $this->website . 'article/'.$id[$i-1].'.html';
            $data[$i]['changefreq'] = 'daily';
            $data[$i]['score'] = '0.9';
        }
        return $data;
    }

    //根据网址,数量生成地址 https://mytempsms.com/phone-number/page/[number].html
    public function generateUrlNumber($url, $number){
        $data = [];
        for ($i = 1; $i < $number+1; $i++){
            $data[$i]['url'] = str_replace('[number]', $i, $url);
            $data[$i]['changefreq'] = 'daily';
            $data[$i]['score'] = '0.8';
        }
        return $data;
    }

    //生成单个<url数据>
    public function sitemapUrl($url, $changefreq, $score)
    {
        $sub_sitemap['url'] = $url;
        $sub_sitemap['changefreq'] = $changefreq;
        $sub_sitemap['score'] = $score;
        return $sub_sitemap;
    }

    public function sitemapDom($website, $info)
    {
        // 创建一个DOMDocument对象
        $dom = new \DOMDocument("1.0", "utf-8");
        header("Content-Type: text/xml");

        // 创建根节点
        $urlset = $dom->createElement("urlset");
        $xmlns = $dom->createAttribute('xmlns');
        $xmlnsvalue = $dom->createTextNode("http://www.sitemaps.org/schemas/sitemap/0.9");
        $xmlns -> appendChild($xmlnsvalue);
        $urlset -> appendChild($xmlns);
        $dom->appendChild($urlset);
        for ($i = 0; $i < count($info); $i++) {
            $track = $dom->createElement("url");
            $urlset->appendChild($track);
            // 建立track节点下元素
            $loc = $dom->createElement("loc");
            $track->appendChild($loc);
/*            $lastmod = $dom->createElement("lastmod");
            $track->appendChild($lastmod);*/
            $changefreq = $dom->createElement("changefreq");
            $track->appendChild($changefreq);
            $priority = $dom->createElement("priority");
            $track->appendChild($priority);
            // 赋值
            $text = $dom->createTextNode($info[$i]['url']);
            $loc->appendChild($text);
/*            $date = date("Y-m-d H:s:i", time());
            $text = $dom->createTextNode($date);
            $lastmod->appendChild($text);*/
            $text = $dom->createTextNode($info[$i]['changefreq']);
            $changefreq->appendChild($text);
            $text = $dom->createTextNode($info[$i]['score']);
            $priority->appendChild($text);
        }
        //生成xml文件
        $websites = explode('.', $website);
        if ($websites[0] == 'www'){
            $website = $websites['1'] . '.' . $websites['2'];
        }
        $result = $dom->save($website . "_sitemap.xml");
        //exit;
        return $result;
    }

}