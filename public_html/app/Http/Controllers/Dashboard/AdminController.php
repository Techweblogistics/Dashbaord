<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\PaymentController;
use App\Models\Activity;
use App\Models\ChatCategory;
use App\Models\Clients;
use App\Models\CustomSettings;
use App\Models\Currency;
use App\Models\Faq;
use App\Models\FrontendForWho;
use App\Models\FrontendFuture;
use App\Models\FrontendGenerators;
use App\Models\FrontendSectionsStatusses;
use App\Models\FrontendSetting;
use App\Models\FrontendTools;
use App\Models\Gateways;
use App\Models\GatewayProducts;
use App\Models\OpenAIGenerator;
use App\Models\OpenaiGeneratorChatCategory;
use App\Models\OpenaiGeneratorFilter;
use App\Models\PaymentPlans;
use App\Models\Setting;
use App\Models\Testimonials;
use App\Models\HowitWorks;
use App\Models\User;
use App\Models\UserAffiliate;
use App\Models\UserOpenai;
use App\Models\UserOpenaiChat;
use App\Models\UserOpenaiChatMessage;
use App\Models\UserOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Subscription;
use App\Models\Coupon;
use App\Models\Favourite;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function index(){
        Cache::flush();

        if (
               !Cache::has('sales_this_week')
            or !Cache::has('sales_previous_week')
            or !Cache::has('words_this_week')
            or !Cache::has('words_previous_week')
            or !Cache::has('images_this_week')
            or !Cache::has('images_previous_week')
            or !Cache::has('users_this_week')
            or !Cache::has('users_previous_week')
            or !Cache::has('chat_tokens')
            or !Cache::has('daily_sales')
            or !Cache::has('daily_usages')
            or !Cache::has('top_countries')
            or !Cache::has('total_users')
            or !Cache::has('total_sales')
            or !Cache::has('total_usage')
        ) {
            //Sales this week
            $sales_this_week = UserOrder::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('price');
            Cache::put('sales_this_week', $sales_this_week, now()->addMinutes(360));
            //Sales previous week
            $sales_previous_week = UserOrder::whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])->sum('price');
            Cache::put('sales_previous_week', $sales_previous_week, now()->addMinutes(360));
            //Words this week
            $words_this_week = UserOpenai::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->where('credits', '!=', 1)->sum('credits');
            Cache::put('words_this_week', $words_this_week, now()->addMinutes(360));
            //Words previous week
            $words_previous_week = UserOpenai::whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])->where('credits', '!=', 1)->sum('credits');
            Cache::put('words_previous_week', $words_previous_week, now()->addMinutes(360));
            //Images this week
            $images_this_week = UserOpenai::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->where('credits', '=', 1)->sum('credits');
            Cache::put('images_this_week', $images_this_week, now()->addMinutes(360));
            //Images previous week
            $images_previous_week = UserOpenai::whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])->where('credits', '=', 1)->sum('credits');
            Cache::put('images_previous_week', $images_previous_week, now()->addMinutes(360));

            //user change
            $users_this_week = count(User::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get());
            Cache::put('users_this_week', $users_this_week, now()->addMinutes(360));
            $users_previous_week = count(UserOpenai::whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])->get());
            Cache::put('users_previous_week', $users_previous_week, now()->addMinutes(360));

            //Chat tokens
            $chat_tokens = UserOpenaiChatMessage::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('credits');
            Cache::put('chat_tokens', $chat_tokens, now()->addMinutes(360));
            //Daily sales
            $daily_sales = UserOrder::select(
                DB::raw('sum(price) as sums'),
                DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d') as days")
            )
                ->groupBy('days')
                ->get();
            Cache::put('daily_sales', json_encode($daily_sales), now()->addMinutes(360));
            // $total_sales = UserOrder::all()->sum('price');
            $total_sales = UserOrder::where('status', 'Success')->sum('price');
            Cache::put('total_sales', $total_sales, now()->addMinutes(360));
            //Usages
            $daily_usages = UserOpenai::select(
                DB::raw('SUM(IF(credits=1,credits,0)) as sumsImage'),
                DB::raw('SUM(IF(credits>1,credits,0)) as sumsWord'),
                DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d') as days")
            )
                ->groupBy('days')
                ->get();
            Cache::put('daily_usages', json_encode($daily_usages), now()->addMinutes(360));
            $total_usage = 0;
            UserOpenai::chunk(1000, function ($users) use (&$total_usage) {
                $total_usage += $users->sum('credits');
            });
            Cache::put('total_usage', $total_usage, now()->addMinutes(360));
            //Top Countries
            $top_countries = User::select('country', DB::raw('count(*) as total'))
                ->groupBy('country')
                ->get();
            Cache::put('top_countries', json_encode($top_countries), now()->addMinutes(360));
            //Total Users
            Cache::put('total_users', count(User::all()), now()->addMinutes(360));
        }

        // if(!Cache::has('check_gateway_webhooks')){
        //     //Check gateway webhooks if necessary
        //     GatewayController::checkGatewayWebhooks();
        //     Cache::put('check_gateway_webhooks', true, now()->addMinutes(2880)); // 2 days
        // }

        //Variables
        $activity = Activity::orderBy('created_at', 'desc')->get();
        $latestOrders = UserOrder::orderBy('created_at', 'desc')->take(10)->get();

        $gatewayError = false;
        // $gateway = Gateways::where("mode", "sandbox")->first();
        // if($gateway != null){
        //     if(env('APP_ENV') != 'development'){
        //         error_log('Gateway is set to use sandbox. Please set mode to development!');
        //         $gatewayError = true;
        //     }
        // }

        $settings = Setting::first();
        $currencySymbol = Currency::where('id', $settings->default_currency)->first()->symbol;
         
        return view('panel.admin.index', compact('activity', 'latestOrders','gatewayError', 'currencySymbol'));
    }

    //USER MANAGEMENT
    public function users(){
        // $users = User::all();
        $users = User::paginate(25);
        return view('panel.admin.users.index', compact('users'));
    }

    public function usersEdit($id){
        $user = User::whereId($id)->firstOrFail();
        return view('panel.admin.users.edit', compact('user'));
    }

    public function usersDelete($id){
        $user = User::whereId($id)->firstOrFail();
        $user->delete();
        return back()->with(['message' => 'Deleted Successfully', 'type' => 'success']);
    }

    public function usersSave(Request $request){
        $user = User::whereId($request->user_id)->firstOrFail();

        $user->name = $request->name;
        $user->surname = $request->surname;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->country = $request->country;
        $user->type = $request->type;
        $user->status = $request->status;
        $user->remaining_words = $request->remaining_words;
        $user->remaining_images = $request->remaining_images;
        $user->save();
    }

    //OPENAI MANAGEMENT
    public function openAIList(){
        $list = OpenAIGenerator::orderBy('title', 'asc')->get();
        return view('panel.admin.openai.list', compact('list'));
    }

    public function openAIListUpdateStatus(Request $request){
        $status = $request->status;
        $openai = OpenAIGenerator::whereId($request->entry_id)->first();
        if ($status  == 1 or $status == 0){
            $openai->active = $status;
            $openai->save();
        }else{
            return response()->json([], 403);
        }

    }

    public function openAIListUpdatePackageStatus(Request $request){
        $status = $request->status;
        $openai = OpenAIGenerator::whereId($request->entry_id)->first();
        if ($status  == 1 or $status == 0){
            $openai->premium = $status;
            $openai->save();
        }else{
            return response()->json([], 403);
        }

    }

    public function categoryList(Request $request) {
        $list = ChatCategory::orderBy('name', 'asc')->get();
        return view('panel.admin.openai.chat.category', compact('list'));
    }

    public function addOrUpdateCategory($id = null) {
        if ($id == null){
            $item = null;
        }else{
            $item = ChatCategory::where('id', $id)->firstOrFail();
        }

        return view('panel.admin.openai.chat.category.template', compact('item'));
    }

    public function chatCategoriesAddOrUpdateSave(Request $request) {
        if ($request->item_id != 'undefined'){
            $item = ChatCategory::where('id', $request->item_id)->firstOrFail();
        }else{
            $item = new ChatCategory();
        }
        $item->name = $request->name;
        $item->save();
    }

    public function chatCategoriesDelete($id = null) {
        $item = ChatCategory::where('id', $id)->firstOrFail();
        $item->delete();
        return back()->with(['message' => 'Deleted Successfully', 'type' => 'success']);        
    }

    //OPENAI CHAT CUSTOM TEMPLATES
    public function openAIChatList(){
        // $list = OpenaiGeneratorChatCategory::orderBy('name', 'asc')->get();
        $list = OpenaiGeneratorChatCategory::where('slug', '<>', 'ai_vision')->orderBy('name', 'asc')->get();
        return view('panel.admin.openai.chat.list', compact('list'));
    }

    public function openAIChatAddOrUpdate($id = null){
        if ($id == null){
            $template = null;
        }else{
            $template = OpenaiGeneratorChatCategory::where('id', $id)->firstOrFail();
        }

        $categoryList = ChatCategory::all();

        return view('panel.admin.openai.chat.form', compact('template', 'categoryList'));
    }

    public function updatePlan(Request $request) {
        $status = $request->status;
        $chat = OpenaiGeneratorChatCategory::whereId($request->entry_id)->first();
        $chat->plan = $status;
        $chat->save();
    }

    public function updateChatFav(Request $req) {
        $id = $req->id;

        $favourites = Favourite::where('type', 'chat')
            ->where('item_id', $id)
            ->where('user_id', auth()->user()->id)
            ->get();

        if ($favourites->count() != 0) {
             $favourites->each->delete();
        } else {
            $favourite = new Favourite();
            $favourite->user_id = auth()->user()->id;
            $favourite->type = 'chat';
            $favourite->item_id = $id;
            $favourite->save();
        }

        $favourites = Favourite::where('type', 'chat')
            ->where('user_id', auth()->user()->id)
            ->get();
        return response()->json($favourites);
    }

    public function openAIChatDelete($id = null){
        $template = OpenaiGeneratorChatCategory::where('id', $id)->firstOrFail();
        $template->delete();
        return back()->with(['message' => 'Deleted Successfully', 'type' => 'success']);
    }

    public function openAIChatAddOrUpdateSave(Request $request){

        if ($request->template_id != 'undefined'){
            $template = OpenaiGeneratorChatCategory::where('id', $request->template_id)->firstOrFail();
        }else{
            $template = new OpenaiGeneratorChatCategory();
        }

        if ($request->hasFile('avatar')) {
            $path = 'upload/images/chatbot/';
            $image = $request->file('avatar');
            $image_name = Str::random(4) . '-' . Str::slug($request->name) . '-avatar.' . $image->getClientOriginalExtension();

            //Resim uzantı kontrolü
            $imageTypes = ['jpg', 'jpeg', 'png', 'svg', 'webp'];
            if (!in_array(Str::lower($image->getClientOriginalExtension()), $imageTypes)) {
                $data = array(
                    'errors' => ['The file extension must be jpg, jpeg, png, webp or svg.'],
                );
                return response()->json($data, 419);
            }

            $image->move($path, $image_name);

            $template->image = $path . $image_name;
        }

        $template->name = $request->name;
        error_log($request->category);
        if($request->category == "") {
            $template->category = "";
        } else {
            $template->category = $request->category;
        }
        $template->slug = Str::slug($request->name).'-'.Str::random(5);
        $template->short_name = $request->short_name;
        $template->description = $request->description;
        $template->role = $request->role;
        $template->human_name = $request->human_name;
        $template->helps_with = $request->helps_with;
        $template->color = $request->color;
        $template->plan = "regular";
        $template->chat_completions = $request->chat_completions;
        $template->prompt_prefix = "As a ".$request->role.", ";
        $template->save();
    }

    //OPENAI CUSTOM TEMPLATES
    public function openAICustomList(){
        $list = OpenAIGenerator::orderBy('title', 'asc')->where('custom_template', 1)->get();
        return view('panel.admin.openai.custom.list', compact('list'));
    }

    public function openAICustomAddOrUpdate($id = null){
        if ($id == null){
            $template = null;
        }else{
            $template = OpenAIGenerator::where('id', $id)->firstOrFail();
        }
        $filters = OpenaiGeneratorFilter::orderBy('name', 'desc')->get();
        return view('panel.admin.openai.custom.form', compact('template', 'filters'));
    }

    public function openAICustomDelete($id = null){
        $template = OpenAIGenerator::where('id', $id)->firstOrFail();
        $template->delete();
        return back()->with(['message' => 'Deleted Successfully', 'type' => 'success']);
    }

    public function openAICustomAddOrUpdateSave(Request $request){

        if ($request->template_id != 'undefined'){
            $template = OpenAIGenerator::where('id', $request->template_id)->firstOrFail();
        }else{
            $template = new OpenAIGenerator();
        }

        $template->title = $request->title;
        $template->description = $request->description;
        $template->image = $request->image;
        $template->color = $request->color;
        $template->prompt = $request->prompt;

        $inputNames = explode( ',', $request->input_name);
        $inputDescriptions = explode( ',', $request->input_description);
        $inputTypes = explode( ',', $request->input_type);

        $i = 0;
        $array = [];
        foreach ($inputNames as $inputName){
            $array[$i]['name'] = Str::slug($inputName);
            $array[$i]['type'] = $inputTypes[$i];
            $array[$i]['question'] = $inputName;
            $array[$i]['description'] = $inputDescriptions[$i];
            $i++;
        }

        $questions = json_encode($array,JSON_UNESCAPED_SLASHES);
        $template->active = 1;
        $template->slug = Str::slug($request->title).'-'.Str::random(6);
        $template->questions = $questions;
        $template->type = 'text';
        $template->custom_template = 1;
        $template->filters = $request->filters;
        foreach (explode( ',', $request->filters) as $filter){
            if (OpenaiGeneratorFilter::where('name', $filter)->first() == null){
                $newFilter = new OpenaiGeneratorFilter();
                $newFilter->name = $filter;
                $newFilter->save();
            }
        }
        $template->premium = $request->premium;

        $template->save();
    }

    //Openai Categories
    public function openAICategoriesList(){
        $list = OpenaiGeneratorFilter::orderBy('name', 'asc')->get();
        return view('panel.admin.openai.categories.list', compact('list'));
    }

    public function openAICategoriesAddOrUpdate($id = null){
        if ($id == null){
            $item = null;
        }else{
            $item = OpenaiGeneratorFilter::where('id', $id)->firstOrFail();
        }
        return view('panel.admin.openai.categories.form', compact('item'));
    }

    public function openAICategoriesDelete($id = null){
        $item = OpenaiGeneratorFilter::where('id', $id)->firstOrFail();
        $item->delete();
        return back()->with(['message' => 'Deleted Successfully', 'type' => 'success']);
    }

    public function openAICategoriesAddOrUpdateSave(Request $request){

        if ($request->item_id != 'undefined'){
            $item = OpenaiGeneratorFilter::where('id', $request->item_id)->firstOrFail();
        }else{
            $item = new OpenaiGeneratorFilter();
        }
        $item->name = $request->name;
        $item->save();
    }

    //FINANCE

    //Payment

    public function paymentPlans(){

        $gatewayError = false;
        // $gateway = Gateways::where("mode", "sandbox")->first();
        // if($gateway != null){
        //     if(env('APP_ENV') != 'development'){
        //         error_log('Gateway is set to use sandbox. Please set mode to development!');
        //         $gatewayError = true;
        //     }
        // }
        $setting =  Setting::first();
        $plans = PaymentPlans::all();
        return view('panel.admin.finance.plans.index', compact('plans', 'gatewayError', 'setting'));
    }

    public function paymentPlansSubscriptionNewOrEdit($id = null){

        $activeGateways = Gateways::where('is_active', 1)->get();
        if($activeGateways->count() > 0){
            $isActiveGateway = 1;
        }else{
            $isActiveGateway = 0;
        }

        $generatedData = null;
        if($id != null){
            $generatedData = GatewayProducts::where('plan_id', $id)->get();
        }

        if ($id == null){
            return view('panel.admin.finance.plans.SubscriptionNewOrEdit', compact('isActiveGateway'));
        }else{
            $subscription = PaymentPlans::where('id', $id)->first();
            return view('panel.admin.finance.plans.SubscriptionNewOrEdit', compact('subscription', 'isActiveGateway', 'generatedData'));
        }
    }

    public function paymentPlansDelete($id){
        return PaymentController::deletePaymentPlan($id);
    }

    public function paymentPlansPrepaidNewOrEdit($id = null){

        $activeGateways = Gateways::where('is_active', 1)->get();
        if($activeGateways->count() > 0){
            $isActiveGateway = 1;
        }else{
            $isActiveGateway = 0;
        }

        $generatedData = null;
        if($id != null){
            $generatedData = GatewayProducts::where('plan_id', $id)->get();
        }

        if ($id == null){
            return view('panel.admin.finance.plans.PrepaidNewOrEdit', compact('isActiveGateway'));
        }else{
            $subscription = PaymentPlans::where('id', $id)->first();
            return view('panel.admin.finance.plans.PrepaidNewOrEdit', compact('subscription', 'isActiveGateway', 'generatedData'));
        }
    }


    public function paymentPlansSave(Request $request){
        if ($request->plan_id != 'undefined'){
            $plan = PaymentPlans::where('id', $request->plan_id)->firstOrFail();
        }else{
            $plan = new PaymentPlans();
        }

        if ($request->type == 'subscription'){
            $plan->active = 1;
            $plan->name = $request->name;
            $plan->price = (double)$request->price;
            $plan->frequency = $request->frequency;
            $plan->is_featured = (int)$request->is_featured;
            $plan->stripe_product_id = $request->stripe_product_id;
            $plan->total_words = (int)$request->total_words;
            $plan->total_images = (int)$request->total_images;
            $plan->ai_name = $request->ai_name;
            // $plan->max_tokens = (int)$request->max_tokens;
            $plan->can_create_ai_images = (int)$request->can_create_ai_images;
            $plan->plan_type = $request->plan_type;
            $plan->features = $request->features;
            $plan->trial_days = $request->trial_days;
            $plan->type = $request->type;
            $plan->display_imag_count = $request->display_img;
            $plan->display_word_count = $request->display_word;
            $plan->save();

        }else{
            $plan->active = 1;
            $plan->name = $request->name;
            $plan->price = (double)$request->price;
            $plan->is_featured = (int)$request->is_featured;
            $plan->total_words = (int)$request->total_words;
            $plan->total_images = (int)$request->total_images;
            $plan->features = $request->features;
            $plan->type = $request->type;
            $plan->display_imag_count = $request->display_img;
            $plan->display_word_count = $request->display_word;
            $plan->save();
        }

        try{
            $tmp = PaymentController::saveGatewayProducts($plan->id, $request->name, (double)$request->price, $request->frequency, $request->type);
        }catch(\Exception $ex){
            Log::info($ex->getMessage());
            error_log("AdminController->paymentPlansSave()->PaymentController::saveGatewayProducts()\n".$ex->getMessage());
            return back()->with(['message' => $ex->getMessage(), 'type' => 'error']);
        }
    }

    // Testimonials

    /**
     * Index page of Testimonials in Admin
     */
    public function testimonials(){
        $testimonialList = Testimonials::all();
        return view('panel.admin.testimonials.index', compact('testimonialList'));
    }

    public function testimonialsNewOrEdit($id = null){
        if ($id == null){
            return view('panel.admin.testimonials.TestimonialNewOrEdit');
        }else{
            $testimonial = Testimonials::where('id', $id)->first();
            return view('panel.admin.testimonials.TestimonialNewOrEdit', compact('testimonial'));
        }
    }

    public function testimonialsDelete($id){
        $testimonial = Testimonials::where('id', $id)->first();
        $testimonial->delete();
        return back()->with(['message' => 'Testimonial is deleted.', 'type' => 'success']);
    }


    public function testimonialsSave(Request $request){
        if ($request->testimonial_id != 'undefined'){
            $testimonial = Testimonials::where('id', $request->testimonial_id)->firstOrFail();
        }else{
            $testimonial = new Testimonials();
        }

        if($request->file('avatar')){
            $file= $request->file('avatar');
            $filename= date('YmdHi').$file->getClientOriginalName();
            $file-> move(public_path('testimonialAvatar'), $filename);
            $testimonial->avatar = $filename;
        }

        $testimonial->full_name = $request->full_name;
        $testimonial->job_title = $request->job_title;
        $testimonial->words = $request->words;
        $testimonial->save();
    }


    // Testimonials End

    // How it Works
    public function howitWorksDefaults(){
        $values = json_decode('{"option": TRUE, "html": ""}');
        $default_html = 'Want to see? <a class="text-[#FCA7FF]" href="https://codecanyon.net/item/magicai-openai-content-text-image-chat-code-generator-as-saas/45408109" target="_blank">'.__('Join').' Magic</a>';

        //Check display bottom line
        $bottomline = CustomSettings::where('key', 'howitworks_bottomline')->first();
        if($bottomline != null){
            $values["option"] = $bottomline->value_int ?? 1;
            $values["html"] = $bottomline->value_html ?? $default_html;
        }else{
            $bottomline = new CustomSettings();
            $bottomline->key = 'howitworks_bottomline';
            $bottomline->title = 'Used in How it Works section bottom line. Controls visibility and HTML value of line.';
            $bottomline->value_int = 1;
            $bottomline->value_html = $default_html;
            $bottomline->save();
            $values["option"] = 1;
            $values["html"] = $default_html;
        }

        return $values;
    }

    /**
     * Index page of "How it Works" in Admin
     */
    public function howitWorks(){
        $howitWorksList = HowitWorks::all();
        $defaults = self::howitWorksDefaults();
        return view('panel.admin.howitworks.index', compact('howitWorksList', 'defaults'));
    }



    public function howitWorksNewOrEdit($id = null){
        if ($id == null){
            return view('panel.admin.howitworks.HowitWorksNewOrEdit');
        }else{
            $howitWorks = HowitWorks::where('id', $id)->first();
            return view('panel.admin.howitworks.HowitWorksNewOrEdit', compact('howitWorks'));
        }
    }

    public function howitWorksDelete($id){
        $howitWorks = HowitWorks::where('id', $id)->first();
        $howitWorks->delete();
        return back()->with(['message' => 'How it Works Step is deleted.', 'type' => 'success']);
    }


    public function howitWorksSave(Request $request){
        if ($request->howitWorks_id != 'undefined'){
            $howitWorks = HowitWorks::where('id', $request->howitWorks_id)->firstOrFail();
        }else{
            $howitWorks = new HowitWorks();
        }

        $howitWorks->order = (int)$request->order;
        $howitWorks->title = $request->title;
        $howitWorks->save();
    }

    public function howitWorksBottomLineSave(Request $request){

        $bottomline = CustomSettings::where('key', 'howitworks_bottomline')->first();
        if($bottomline != null){

            $save = 0;
            if($request->option != 'undefined' && $request->option != null){
                $bottomline->value_int = $request->option == 1 ? 1 : 0 ;
                $save=1;
            }

            if($request->text != 'undefined' && $request->text != null){
                $default_html = 'Want to see? <a class="text-[#FCA7FF]" href="https://codecanyon.net/item/magicai-openai-content-text-image-chat-code-generator-as-saas/45408109" target="_blank">'.__('Join').' Magic</a>';
                $bottomline->value_html = $request->text ?? $default_html ;
                $save=1;
            }

            if($save == 1){
                $bottomline->save();
            }

        }
    }

    // "How it Works" End

    // Clients => Bottom section of Testimonials

    /**
     * Index page of Clients in Admin
     */
    public function clients(){
        $clientList = Clients::all();
        return view('panel.admin.clients.index', compact('clientList'));
    }

    public function clientsNewOrEdit($id = null){
        if ($id == null){
            return view('panel.admin.clients.ClientNewOrEdit');
        }else{
            $client = Clients::where('id', $id)->first();
            return view('panel.admin.clients.ClientNewOrEdit', compact('client'));
        }
    }

    public function clientsDelete($id){
        $client = Clients::where('id', $id)->first();
        $client->delete();
        return back()->with(['message' => 'Client deleted.', 'type' => 'success']);
    }


    public function clientsSave(Request $request){
        if ($request->client_id != 'undefined'){
            $client = Clients::where('id', $request->client_id)->firstOrFail();
        }else{
            $client = new Clients();
        }

        if($request->file('avatar')){
            $file= $request->file('avatar');
            $filename= date('YmdHi').$file->getClientOriginalName();
            $file-> move(public_path('clientAvatar'), $filename);
            $client->avatar = $filename;
        }

        $client->alt = $request->alt;
        $client->title = $request->title;
        $client->save();
    }


    // Testimonials End


    //Affiliates
    public function affiliatesList(){
        $list = UserAffiliate::where('status', 'Waiting')->get();
        $list2 = UserAffiliate::whereNot('status', 'Waiting')->get();
        return view('panel.admin.affiliate.index', compact('list', 'list2'));
    }
    public function affiliatesListSent($id){
        $item = UserAffiliate::whereId($id)->firstOrFail();
        $item->status = 'Sent Succesfully';
        $item->save();
        return back();
    }

    //Coupons
    public function couponsList(){
        $list = Coupon::get();
        return view('panel.admin.coupons.index', compact('list'));
    }
    public function couponsListUsed($id){
        $coupon = Coupon::find($id);
        return view('panel.admin.coupons.used', compact('coupon'));
    }
    public function couponsDelete($id){
        $coup = Coupon::find($id);
        if($coup){
            $coup->delete();
            return back()->with('success', 'Coupon deleted successfully');
        }
        return back()->with('error', 'Something went wrong!');
    }
    public function couponsAdd(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'discount' => 'required|numeric|min:0|max:100',
            'limit' => 'required|integer|min:-1',
            'code' => 'required|in:auto,manual',
            'codeInput' => 'required_if:code,manual|max:20',
        ]);
        
        $newCoupon = new Coupon();
        $newCoupon->name = $request->input('name');
        $newCoupon->discount = $request->input('discount');
        $newCoupon->limit = $request->input('limit');
        $newCoupon->created_by = auth()->user()->id;

        
        // Check if the "code" field is set to "manual" and set the "codeInput" attribute accordingly.
        if ($request->input('code') === 'manual') {
            if (Coupon::where('code', $request->input('codeInput'))->exists()) {
                $newCoupon->code = $this->generateUniqueCode(); 
            }else{
                $newCoupon->code = $request->input('codeInput');
            }
        }else{
            $newCoupon->code = $this->generateUniqueCode();
        }
        
        $newCoupon->save();
        return redirect()->back()->with('success', 'Coupon created successfully.');
    }
    public function couponsEdit(Request $request, $id){
        $request->validate([
            'name' => 'required|string|max:255',
            'discount' => 'required|numeric|min:0|max:100',
            'limit' => 'required|integer|min:-1'
        ]);

        $newCoupon = Coupon::find($id);
        $newCoupon->name = $request->input('name');
        $newCoupon->discount = $request->input('discount');
        $newCoupon->limit = $request->input('limit');

        $newCoupon->save();
        return redirect()->back()->with('success', 'Coupon created successfully.');
    }
    public function couponsValidate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'errors' => $validator->errors(),
            ]);
        }

        $isValidCoupon = $this->checkCouponValidity($request->input('code'));
        return response()->json(['valid' => $isValidCoupon]);
    }

    private function checkCouponValidity($code)
    {
        $exist = Coupon::where('code',$code)->first();
        if ($exist && (!($exist->usersUsed->count() >= $exist->limit) || $exist->limit == -1)) {
            return true;
        }
        return false; 
    }
    private function generateUniqueCode() {
        $code = $this->generateRandomCode(); // Generate a random code initially.
        // Check if the generated code already exists in the database.
        while (Coupon::where('code', $code)->exists()) {
            $code = $this->generateRandomCode(); // Generate a new code if it already exists.
        }
        return $code;
    }
    private function generateRandomCode($length = 7) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
    
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
    
        return $code;
    }

    //Frontend
    public function frontendSettings(){
        return view('panel.admin.frontend.settings');
    }
    public function frontendSettingsSave(Request $request){


        if (env('APP_STATUS') != 'Demo'){
            $settings = Setting::first();
            $settings->site_name = $request->site_name;
            $settings->site_url = substr($request->site_url, -1) === '/' ? substr($request->site_url, 0, -1) : $request->site_url;
            $settings->site_email = $request->site_email;
            $settings->frontend_pricing_section = $request->frontend_pricing_section;
            $settings->frontend_custom_templates_section = $request->frontend_custom_templates_section;
            $settings->frontend_additional_url = $request->frontend_additional_url;
            $settings->frontend_custom_css = $request->frontend_custom_css;
            $settings->frontend_custom_js = $request->frontend_custom_js;
            $settings->frontend_footer_facebook = $request->frontend_footer_facebook;
            $settings->frontend_footer_twitter = $request->frontend_footer_twitter;
            $settings->frontend_footer_instagram = $request->frontend_footer_instagram;
            $settings->frontend_code_before_head = $request->frontend_code_before_head;
            $settings->frontend_code_before_body = $request->frontend_code_before_body;
            $settings->save();

            $fSettings = FrontendSetting::first();
            $fSettings->header_title = $request->header_title;
            $fSettings->header_text = $request->header_text;

            $fSettings->sign_in = $request->sign_in;
            $fSettings->join_hub = $request->join_hub;

            $fSettings->hero_subtitle = $request->hero_subtitle;
            $fSettings->hero_title = $request->hero_title;
            $fSettings->hero_title_text_rotator = $request->hero_title_text_rotator;
            $fSettings->hero_description = $request->hero_description;
            $fSettings->hero_scroll_text = $request->hero_scroll_text;
            $fSettings->hero_button = $request->hero_button;
            $fSettings->hero_button_url = $request->hero_button_url;
            $fSettings->hero_button_type = $request->hero_button_type;

            $fSettings->floating_button_small_text = $request->floating_button_small_text;
            $fSettings->floating_button_bold_text = $request->floating_button_bold_text;
            $fSettings->floating_button_link = $request->floating_button_link;
            $fSettings->floating_button_active = $request->floating_button_active;

            $fSettings->footer_header = $request->footer_header;
            $fSettings->footer_text_small = $request->footer_text_small;
            $fSettings->footer_text = $request->footer_text;
            $fSettings->footer_button_text = $request->footer_button_text;
            $fSettings->footer_button_url = $request->footer_button_url;
            $fSettings->footer_copyright = $request->footer_copyright;
            $fSettings->save();
            
            $fSecSettings = FrontendSectionsStatusses::first();
            $fSecSettings->preheader_active = $request->preheader_active;
            $fSecSettings->save();

            $logo_types = [
                'logo' => '',
                'logo_dark' => 'dark',
                'logo_sticky' => 'sticky',
                'logo_dashboard' => 'dashboard',
                'logo_dashboard_dark' => 'dashboard-dark',
                'logo_collapsed' => 'collapsed',
                'logo_collapsed_dark' => 'collapsed-dark',
                // retina
                'logo_2x' => '2x',
                'logo_dark_2x' => 'dark-2x',
                'logo_sticky_2x' => 'sticky-2x',
                'logo_dashboard_2x' => 'dashboard-2x',
                'logo_dashboard_dark_2x' => 'dashboard-dark-2x',
                'logo_collapsed_2x' => 'collapsed-2x',
                'logo_collapsed_dark_2x' => 'collapsed-dark-2x',
            ];

            foreach( $logo_types as $logo => $logo_prefix ) {

                if ($request->hasFile($logo)) {
                    $path = 'upload/images/logo/';
                    $image = $request->file($logo);
                    $image_name = Str::random(4) . '-'. $logo_prefix .'-' . Str::slug($settings->site_name) . '-logo.' . $image->getClientOriginalExtension();

                    //Resim uzantı kontrolü
                    $imageTypes = ['jpg', 'jpeg', 'png', 'svg', 'webp'];
                    if (!in_array(Str::lower($image->getClientOriginalExtension()), $imageTypes)) {
                        $data = array(
                            'errors' => ['The file extension must be jpg, jpeg, png, webp or svg.'],
                        );
                        return response()->json($data, 419);
                    }

                    $image->move($path, $image_name);

                    $settings->{$logo.'_path'} = $path . $image_name;
                    $settings->{$logo} = $image_name;
                    $settings->save();
                }

            }

            if ($request->hasFile('favicon')){
                $path = 'upload/images/favicon/';
                $image = $request->file('favicon');
                $image_name = Str::random(4).'-'.Str::slug($settings->site_name).'-favicon.'.$image->getClientOriginalExtension();

                //Resim uzantı kontrolü
                $imageTypes = ['jpg', 'jpeg', 'png', 'svg', 'webp'];
                if (!in_array(Str::lower($image->getClientOriginalExtension()), $imageTypes)){
                    $data = array(
                        'errors' => ['The file extension must be jpg, jpeg, png, webp or svg.'],
                    );
                    return response()->json($data, 419);
                }

                $image->move($path, $image_name);

                $settings->favicon_path = $path.$image_name;
                $settings->favicon = $image_name;
                $settings->save();
            }
        }

    }

    //Section Settings
    public function frontendSectionSettings(){
        return view('panel.admin.frontend.section_settings');
    }
    public function frontendSectionSettingsSave(Request $request){

        if (env('APP_STATUS') != 'Demo'){
            $settings = FrontendSectionsStatusses::first();
            $settings->features_active = $request->features_active;
            $settings->features_title = $request->features_title;
            $settings->features_description = $request->features_description;

            $settings->generators_active = $request->generators_active;

            $settings->who_is_for_active = $request->who_is_for_active;

            $settings->custom_templates_active = $request->custom_templates_active;
            $settings->custom_templates_subtitle_one = $request->custom_templates_subtitle_one;
            $settings->custom_templates_subtitle_two = $request->custom_templates_subtitle_two;
            $settings->custom_templates_title = $request->custom_templates_title;
            $settings->custom_templates_description = $request->custom_templates_description;

            $settings->tools_active = $request->tools_active;
            $settings->tools_title = $request->tools_title;
            $settings->tools_description = $request->tools_description;

            $settings->how_it_works_active = $request->how_it_works_active;
            $settings->how_it_works_title = $request->how_it_works_title;

            $settings->testimonials_active = $request->testimonials_active;
            $settings->testimonials_title = $request->testimonials_title;
            $settings->testimonials_subtitle_one = $request->testimonials_subtitle_one;
            $settings->testimonials_subtitle_two = $request->testimonials_subtitle_two;

            $settings->pricing_active = $request->pricing_active;
            $settings->pricing_title = $request->pricing_title;
            $settings->pricing_description = $request->pricing_description;
            $settings->pricing_save_percent = $request->pricing_save_percent;

            $settings->faq_active = $request->faq_active;
            $settings->faq_title = $request->faq_title;
            $settings->faq_subtitle = $request->faq_subtitle;
            $settings->faq_text_one = $request->faq_text_one;
            $settings->faq_text_two = $request->faq_text_two;

            $settings->blog_active = $request->blog_active;
            $settings->blog_title = $request->blog_title;
            $settings->blog_subtitle = $request->blog_subtitle;
            $settings->blog_posts_per_page = $request->blog_posts_per_page;
            $settings->blog_button_text = $request->blog_button_text;
            $settings->blog_a_title = $request->blog_a_title;
            $settings->blog_a_subtitle = $request->blog_a_subtitle;
            $settings->blog_a_description = $request->blog_a_description;
            $settings->blog_a_posts_per_page = $request->blog_a_posts_per_page;

            $settings->save();

        }

    }

    //Menu
    public function menuSettings(){
        return view('panel.admin.frontend.menu');
    }

    public function menuSettingsSave(Request $request){

        if (env('APP_STATUS') != 'Demo'){
            $settings = Setting::first();
            $settings->menu_options = $request->menu_options;
            $settings->save();
        }

    }

    //Frontend Frequently asked questions, FAQ
    public function frontendFaq(){
        $faq = Faq::orderBy('created_at', 'desc')->get();
        return view('panel.admin.frontend.faq.index', compact('faq'));
    }


    public function frontendFaqcreateOrUpdate($id = null){
        if ($id == null){
            $faq = null;
        }else{
            $faq = Faq::where('id', $id)->firstOrFail();
        }
        return view('panel.admin.frontend.faq.form', compact('faq'));
    }

    public function frontendFaqcreateOrUpdateSave(Request $request){
        if ($request->faq_id != 'undefined'){
            $faq = Faq::where('id', $request->faq_id)->firstOrFail();
        }else{
            $faq = new Faq();
        }

        $faq->question = $request->question;
        $faq->answer = $request->answer;
        $faq->save();
    }

    public function frontendFaqDelete($id){
        $faq = Faq::where('id', $id)->firstOrFail();
        $faq->delete();

        return back()->with(['message' => 'Faq deleted succesfully', 'type' => 'success']);
    }



    //Frontend Tools Section

    public function frontendTools(){
        $items = FrontendTools::orderBy('created_at', 'desc')->get();
        return view('panel.admin.frontend.tools.index', compact('items'));
    }


    public function frontendToolscreateOrUpdate($id = null){
        if ($id == null){
            $item = null;
        }else{
            $item = FrontendTools::where('id', $id)->firstOrFail();
        }
        return view('panel.admin.frontend.tools.form', compact('item'));
    }

    public function frontendToolscreateOrUpdateSave(Request $request){
        if ($request->item_id != 'undefined'){
            $item = FrontendTools::where('id', $request->item_id)->firstOrFail();
        }else{
            $item = new FrontendTools();
        }
        $item->title = $request->title;
        $item->description = $request->description;

        if ($request->hasFile('image')) {
            $path = 'upload/images/frontent/tools/';
            $image = $request->file('image');
            $image_name = Str::random(4) . '-' . Str::slug($request->title) . '.' . $image->getClientOriginalExtension();

            //Resim uzantı kontrolü
            $imageTypes = ['jpg', 'jpeg', 'png', 'svg', 'webp'];
            if (!in_array(Str::lower($image->getClientOriginalExtension()), $imageTypes)) {
                $data = array(
                    'errors' => ['The file extension must be jpg, jpeg, png, webp or svg.'],
                );
                return response()->json($data, 419);
            }

            $image->move($path, $image_name);

            $item->image = $path . $image_name;
        }

        $item->save();
    }

    public function frontendToolsDelete($id){
        $item = FrontendTools::where('id', $id)->firstOrFail();
        $item->delete();
        return back()->with(['message' => 'Item deleted succesfully', 'type' => 'success']);
    }







    //Future of ai section
    public function frontendFuture(){
        $items = FrontendFuture::orderBy('created_at', 'desc')->get();
        return view('panel.admin.frontend.future.index', compact('items'));
    }


    public function frontendFuturecreateOrUpdate($id = null){
        if ($id == null){
            $item = null;
        }else{
            $item = FrontendFuture::where('id', $id)->firstOrFail();
        }
        return view('panel.admin.frontend.future.form', compact('item'));
    }

    public function frontendFuturecreateOrUpdateSave(Request $request){
        if ($request->item_id != 'undefined'){
            $item = FrontendFuture::where('id', $request->item_id)->firstOrFail();
        }else{
            $item = new FrontendFuture();
        }
        $item->title = $request->title;
        $item->description = $request->description;
        $item->image = $request->image;
        $item->save();
    }

    public function frontendFutureDelete($id){
        $item = FrontendFuture::where('id', $id)->firstOrFail();
        $item->delete();
        return back()->with(['message' => 'Item deleted succesfully', 'type' => 'success']);
    }






    //Who is for section
    public function frontendWhois(){
        $items = FrontendForWho::orderBy('created_at', 'desc')->get();
        return view('panel.admin.frontend.who_is_for.index', compact('items'));
    }


    public function frontendWhoiscreateOrUpdate($id = null){
        if ($id == null){
            $item = null;
        }else{
            $item = FrontendForWho::where('id', $id)->firstOrFail();
        }
        return view('panel.admin.frontend.who_is_for.form', compact('item'));
    }

    public function frontendWhoiscreateOrUpdateSave(Request $request){
        if ($request->item_id != 'undefined'){
            $item = FrontendForWho::where('id', $request->item_id)->firstOrFail();
        }else{
            $item = new FrontendForWho();
        }
        $item->title = $request->title;
        $item->color = $request->color;
        $item->save();
    }

    public function frontendWhoisDelete($id){
        $item = FrontendForWho::where('id', $id)->firstOrFail();
        $item->delete();
        return back()->with(['message' => 'Item deleted succesfully', 'type' => 'success']);
    }


    //Generator list


    public function frontendGeneratorlist(){
        $items = FrontendGenerators::orderBy('created_at', 'desc')->get();
        return view('panel.admin.frontend.generators_list.index', compact('items'));
    }


    public function frontendGeneratorlistcreateOrUpdate($id = null){
        if ($id == null){
            $item = null;
        }else{
            $item = FrontendGenerators::where('id', $id)->firstOrFail();
        }
        return view('panel.admin.frontend.generators_list.form', compact('item'));
    }

    public function frontendGeneratorlistcreateOrUpdateSave(Request $request){
        if ($request->item_id != 'undefined'){
            $item = FrontendGenerators::where('id', $request->item_id)->firstOrFail();
        }else{
            $item = new FrontendGenerators();
        }

        if ($request->hasFile('image')){
            $path = 'upload/images/generatorlist/';
            $image = $request->file('image');
            $image_name = Str::random(4).'-'.Str::slug($request->title).'-image.'.$image->getClientOriginalExtension();

            //Resim uzantı kontrolü
            $imageTypes = ['jpg', 'jpeg', 'png', 'svg', 'webp'];
            if (!in_array(Str::lower($image->getClientOriginalExtension()), $imageTypes)){
                $data = array(
                    'errors' => ['The file extension must be jpg, jpeg, png, webp or svg.'],
                );
                return response()->json($data, 419);
            }

            $image->move($path, $image_name);

            $item->image = $path.$image_name;
        }




        $item->menu_title = $request->menu_title;
        $item->subtitle_one = $request->subtitle_one;
        $item->subtitle_two = $request->subtitle_two;
        $item->title = $request->title;
        $item->text = $request->text;
        $item->image_title = $request->image_title;
        $item->image_subtitle = $request->image_subtitle;
        $item->color = $request->color;
        $item->save();
    }

    public function frontendGeneratorlistDelete($id){
        $item = FrontendGenerators::where('id', $id)->firstOrFail();
        $item->delete();
        return back()->with(['message' => 'Item deleted succesfully', 'type' => 'success']);
    }
}

