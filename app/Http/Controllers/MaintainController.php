<?php

namespace App\Http\Controllers;

// https://laravel.com/docs/8.x/http-client
use App\Category;
use App\Project;
use Github\Api\User;
use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

use Github\ResultPager;
use GrahamCampbell\GitHub\GitHubManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MaintainController extends Controller
{

    protected $githubOrgName = "cepdnaclk";
    protected $githubPage = "https://cepdnaclk.github.io/";

    //protected $baseRepository = "https://cepdnaclk.github.io/projects";
    protected $baseRepository = "https://nuwanj.github.io/ce-projects-data-repository";

    //protected $categoryURL = "https://cepdnaclk.github.io/projects/data/categories";

    protected $client;
    protected $paginator;

    public function __construct(GitHubManager $manager)
    {
        $this->client = $manager->connection();
        $this->paginator = new \Github\ResultPager($this->client);

        //$this->middleware('auth');

    }

    public function updateCategories()
    {
        $url = $this->baseRepository . "/data/categories/list.json";

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request('GET', $url);

            if ($response->getStatusCode() == 200) {

                $data = json_decode($response->getBody(), true);

                //DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                Category::truncate(); // clear the table

                //dd($data);

                foreach ($data as $key => $category) {
                    // fetch each project, one by one and add to the categories table
                    $categoryURL = $this->baseRepository . "/data/categories/" . $key . "/index.json";
                    $response = $client->request('GET', $categoryURL);
                    $catData = json_decode($response->getBody(), true);

                    // TODO: Validate the exist of images
                    $coverURL = $this->baseRepository . "/data/categories/" . $key . "/" . $catData['images']['cover'];
                    $thumbURL = $this->baseRepository . "/data/categories/" . $key . "/" . $catData['images']['thumbnail'];

                    print_r($catData);
                    print "<br>";


                    if ($catData != null) {

                        // TODO: Need to run validator before create

                        /*$catData = request()->validate([
                            'title' => 'required',
                            'code' => [
                                'required',
                                Rule::unique('categories')],
                            'description'=>'nullable',

                            'images.cover'=>'nullable',
                            'images.thumbnail'=>'nullable',

                            'filters' => 'array',
                            'contact' => 'email|nullable'
                        ]);*/

                        $c = new Category();
                        $c->title = $catData['title'];
                        $c->type = $catData['type'];
                        $c->category_code = $catData['code'];
                        $c->description = $catData['description'];
                        $c->cover_image = $coverURL;
                        $c->thumb_image = $thumbURL;
                        $c->filters = $catData['filters'];
                        $c->contact = $catData['contact'];
                        $c->save();

                        echo "$key:<br>";
                        echo (json_encode($catData)) . "<br>";

                    } else {
                        echo "$key: $categoryURL not found or invalid<br>";
                    }
                }

            } else {
                echo "file not found";
            }
            //dd($data);

        } catch (Exception $e) {
            echo 'Message: ' . $e->getMessage();

        }


    }

    public function updateProjects()
    {
        $categories = Category::all();
        Project::deleteAll();

        foreach ($categories as $category) {
            $category_code = $category->category_code;

            $request = Request::create(route('api.repository.filter', $category_code), 'GET');
            $response = Route::dispatch($request);
            $data = json_decode($response->getContent(), true);

            foreach ($data['repositories'] as $key => $project) {
                // TODO: require some validation

                $p = new Project();

                $p->title = $project['title'];
                $p->name = $project['name'];
                $p->repo_name = $project['full_name'];
                $p->organization = $project['organization'];

                $p->description = $project['description'];

                $p->batch = $project['batch'];
                $p->main_category = $project['category'];

                $p->repoLink = $project['repoLink'];
                $p->pageLink = $project['pageLink'];

                $p->has_pages = $project['has_pages'];
                $p->has_wiki = $project['has_wiki'];
                $p->private = $project['private'];

                $p->language = $project['language'];

                $p->forks = $project['forks'];
                $p->watchers = $project['watchers'];
                $p->stars = $project['stars'];

                // Find for repository own image. If there isn't, use the default one
                $p->image = ($project['coverImgLink'] != "") ? $project['coverImgLink'] : $category->cover_image;
                $p->thumbnail = ($project['thumbImgLink'] != "") ? $project['thumbImgLink'] : $category->thumb_image;

                //$p->repo_created = $project['repo_created'];
                //$p->repo_updated = $project['repo_updated'];
                $p->default_branch = $project['default_branch'];

                $status = $p->save();

                $p->categories()->attach($category->id);

                echo $project['title'] . " - $status <br>";
            }
            //echo $response->getContent();

        }


        //dd($categories);

    }

    public function test()
    {
        /*$proj = Project::getByBatch("e16");

        foreach ($proj as $p) {
            echo $p->title."<br>";
        }*/

    }

    public function github()
    {

        $d = [];

        //print($repositories);
        dd($d);

    }
}
