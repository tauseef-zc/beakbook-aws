<?php

namespace App\Http\Controllers;

use Hyn\Tenancy\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Repositories\HostnameRepository;
use Hyn\Tenancy\Repositories\WebsiteRepository;
use Hyn\Tenancy\Environment;

class TenantController extends Controller
{
    public function createTenant(Request $request)
    {
        // This will be the complete website name (tenantUser.mysite.com)
        //$fqdn = sprintf('%s.%s', $this->argument('fqdn'), env('APP_DOMAIN'));
        $companyName = $request->input('companyName');
        $fqdn = sprintf('%s.%s', $companyName, env('APP_DOMAIN'));

       // The website object will save the tenant instance information
       // I recommend to use something random for security reasons
       $website = new Website;
       $website->uuid = env('TENANT_WEBSITE_PREFIX') . Str::random(6);
       app(WebsiteRepository::class)->create($website);

       // The hostname object will save the tenant hosting information, and will be related to the previous created software.
       $hostname = new Hostname;
       $hostname->fqdn = $fqdn;
    //    $hostname = app(HostnameRepository::class)->create($hostname);
       app(HostnameRepository::class)->attach($hostname, $website);

        return $fqdn;
    }

   private function switchEnvironment($hostname, $website)
   {
       $tenancy = app(Environment::class);

       $tenancy->hostname($hostname);

       $tenancy->hostname(); // resolves $hostname as currently active hostname

       $tenancy->tenant($website); // switches the tenant and reconfigures the app

       $tenancy->website(); // resolves $website
       $tenancy->tenant(); // resolves $website

       $tenancy->identifyHostname(); // resets resolving $hostname by using the Request

       return true;
   }
}
