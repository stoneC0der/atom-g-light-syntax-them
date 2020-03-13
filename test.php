<?php

namespace App\Http\Controllers;

use App\Models\ContentManagement;
use App\Models\CustomerQuote;
use App\Models\PageArticle;
use App\Models\Partner;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class HomePage extends Controller
{
  // NOTE:  Temporary use enum for page_id or setup more advance dynamic links
  protected static $home = 1;
  protected static $about = 2;
  protected $client;

  public function __construct()
  {

  }
  public function index()
  {
      $c_profile = file_get_contents(base_path('/storage/data/company-profile.json'));
      $profile_data = json_decode($c_profile, true);
      $company_profile = collect($profile_data[0]['profile']);

      $our_services = PageArticle::with('section')
        ->where('page_id',1)
        ->where('section_id',4)
        ->get();
        // EMPLOYEE SELF-SERVICE (ESS)
      $content = ContentManagement::with('pages', 'sections', 'articles')
        ->where('page_id',1)
        ->where('section_id',1)
        ->Orwhere('section_id',2)
        ->Orwhere('section_id',3)
        ->Orwhere('section_id',6)
        ->Orwhere('section_id',7)
        ->get();

      $customers_quote = CustomerQuote::where('page_id',1)
      ->where('section_id',8)
      ->get();

      $client = new Client();
      // $res = $client->request('GET', 'http://forcefieldsblog.com/api/posts/latests');
      // dd(json_decode($res->getBody()));
      // $blog_posts = json_decode($res->getBody());
      // Transform title, add span to hide part of the title in small screens
      $html_start = '<span class="d-none d-md-inline">/';
      $html_end = '</span>';
      // Transform mission & vision title
      $content[1]->title = $this->transformTitle($content[1]->title,'/','',$html_start,$html_end);
      // transform value & guidance principles title
      $content[2]->title = $this->transformTitle($content[2]->title,'&','',str_replace('/','&',$html_start),$html_end);
      $data = [
        'welcome'         => $content[0],
        'our_vision'      => $content[2],
        'our_mission'     => $content[1],
        'our_services'    => $our_services,
        'contact_info'    => DB::table('company_contact_infos')->select('*')->first(),
        'customers_quote' => $customers_quote,
        'blog_posts'      => [],
        'carousels'       => DB::table('carousels')
          ->select('img','title','subtitle','description')
          ->where('page_id',1)
          ->orderBy('position','asc')
          ->get(),
        'expertise'       => $content[3],
        'ESS'             => $content[4],
        'partners'        => Partner::get(),
        'company_profile' => $company_profile,
      ];

    // dd($data);

    return view('home-page')->with($data);
  }

  /**
   * Display specified resource
   *
   * @return \illuminate\Http\Response
   */
  public function aboutUs()
  {
    $aboutUs = ContentManagement::where('page_id',self::$about)->firstOrFail();
    $data = [
      'about_Us' => $aboutUs,
      'contact_info' => DB::table('company_contact_infos')->select('*')->first(),
      'intro' => $aboutUs,
    ];

    return view('about-us')->with($data);
  }

  /**
         * Edit the specified resource
         *
         * @return \illuminate\Http\Response
         */
        public function getPrivacyStatement()
        {
            $file = base_path('/storage/data/privacyandterms.json');

            if (!is_file($file) && !is_readable($file)) {
                return back()->with('error', 'File does not exits or it is not readable.');
            }
            $content = json_decode(file_get_contents($file,true));
            $privacy = $content[0]->privacy;
            $privacy->content = stripcslashes($privacy->content);

            return view('privacy', compact('privacy'));
        }

        /**
         * Edit the specified resource
         *
         * @return \illuminate\Http\Response
         */
        public function getTermsAndConditions()
        {
            $file = base_path('/storage/data/privacyandterms.json');

            if (!is_file($file) && !is_readable($file)) {
                return back()->with('error', 'File does not exits or it is not readable.');
            }
            $content = json_decode(file_get_contents($file,true));

            $terms = $content[0]->terms;
            $terms->content = stripcslashes($terms->content);
            return view('terms-conditions', compact('terms'));
        }
        /**
         * Transform string to add html before and after a given substring of the string
         * @param string $str The string to transform
         * @param string $delimiter
         * @param string $glue,
         * @param string $html_start
         * @param string $html_end
         *
         * @return string The transformed string
         */
        private function transformTitle($str, $delimiter = " ", $glue = "", $html_start, $html_end)
        {
          if (empty($str) || !is_string($str))
            throw new \Exception("Invalid parameter: method expect a string but, found ".gettype($str));
          if (empty($delimiter) || !is_string($delimiter))
            throw new \Exception("Invalid parameter: method expect a string but, found ".gettype($delimiter));
          $new_title = explode($delimiter, $str);
          $new_title[1] = $html_start.$new_title[1].$html_end;
          return implode($glue,$new_title);
        }
}
