<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\ContractSolutions;
use App\Models\CustomerAdmin;
use App\Models\DataProcess;
use App\Models\Document;
use App\Models\FreeDataProcess;
use App\Models\LogoSetting;
use App\Models\Organization;
use App\Models\CustomerRequest;
use App\Models\Service;
use App\Models\{User, Translation};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\OrganizationalUser;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // return $request->all();
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'services' => 'required|array',
            'is_user_organizational' => 'nullable|boolean',
        ], [
            'name.required' => 'Der Name ist erforderlich.',

            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',

            'password.required' => 'Das Passwort ist erforderlich.',
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',

            'services.required' => 'Der Service ist erforderlich.',
            'services.array' => 'Der Service muss ein Array sein.',

            'is_user_organizational.boolean' => 'Der Organisationsstatus muss ein boolescher Wert sein.',
        ]);


        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);

        if ($request->services) {
            $user->services = $request->services;
        }

        if ($request->org_id) {
            $user->org_id = $request->org_id;
        }

        // Store is_user_organizational value
        $user->is_user_organizational = $request->is_user_organizational;

        $user->save();

        if ($request->creator_id) {
            OrganizationalUser::create([
                'customer_id' => $request->creator_id,
                'user_id' => $user->id
            ]);
            Log::info('organizational user created');
            Log::info('user id', [$user->id]);
        }
        $token = $user->createToken('user_token')->plainTextToken;

        return response()->json([
            "message" => "You are registered successfully. Please verify your email to continue",
            "user" => $user,
            "token" => $token,
        ], 200);
    }
    public function registerCustomer(Request $request)
    {
        // Validate the request input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ], [
            'name.required' => 'Der Name ist erforderlich.',
            'name.string' => 'Der Name muss eine Zeichenkette sein.',
            'name.max' => 'Der Name darf nicht länger als 255 Zeichen sein.',

            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',

            'password.required' => 'Das Passwort ist erforderlich.',
            'password.string' => 'Das Passwort muss eine Zeichenkette sein.',
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',
        ]);

        // Retrieve the services of the user with id = 100
        $firstCustomerAdmin = User::with('customerUserWithNullOrganization')
            ->has('customerUserWithNullOrganization')
            ->where(['is_user_customer' => 1, 'org_id' => null])
            ->first();

        if (!isset($firstCustomerAdmin)) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }

        $user100 = User::where('email', 'uwe.leven@cretschmar.de')->first();

        // Create a new user
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;

        // Get the existing services from user100 and ensure service ID 5 is added
        $services[] = 5; // Assuming this is an array

        $user->services = $services; // Assign updated services array

        $user->org_id = $user100->org_id;
        $user->is_user_organizational = 0;
        $user->password = Hash::make($request->password);

        $user->save();

        // Save the creator and new user in OrganizationalUser
        OrganizationalUser::create([
            'user_id' => $firstCustomerAdmin->customerUserWithNullOrganization->user_id,
            'customer_id' => $user100->id,
            'organizational_id' => $user->id,
        ]);

        LogoSetting::create([
            'user_id'=> $user->id,
            'logo'=> 'logos/1727182162.svg',

        ]);

        // Generate a token for the newly registered user
        $token = $user->createToken('user_token')->plainTextToken;

        // Return a response with the user data and token
        return response()->json([
            "message" => "Customer registered successfully.",
            "user" => $user,
            "token" => $token,
        ], 201);
    }



    public function registerCustomerByAdmin(Request $request)
    {
        // Validate the request input with custom messages
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'services' => 'required|array',
        ], [
            'name.required' => 'Der Name ist erforderlich.',
            'name.string' => 'Der Name muss eine Zeichenkette sein.',
            'name.max' => 'Der Name darf nicht länger als 255 Zeichen sein.',

            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',

            'password.required' => 'Das Passwort ist erforderlich.',
            'password.string' => 'Das Passwort muss eine Zeichenkette sein.',
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',

            'services.required' => 'Der Service ist erforderlich.',
            'services.array' => 'Der Service muss ein Array sein.',
        ]);

        // Create the customer user
        $customerUser = new User();
        $customerUser->name = $request->name;
        $customerUser->email = $request->email;
        $customerUser->is_user_organizational = 1;
        $customerUser->is_user_customer = 1;
        $customerUser->password = Hash::make($request->password);
        $customerUser->services = $request->services;

        if ($request->org_id) {
            $customerUser->org_id = $request->org_id;
        }

        $customerUser->save();

        // // Create a customer request for the newly registered customer
        // $customerRequest = new CustomerAdmin();
        // $customerRequest->user_id = $customerUser->id;
        // $customerRequest->save();

        // Generate a token for the customer user
        $token = $customerUser->createToken('user_token')->plainTextToken;

        // Automatically create the organization user
        $organizationUser = new User();
        $organizationUser->name = "Default Organization User";
        $organizationUser->email = "default_org_user_" . $customerUser->email; // Change email to a default unique value
        $organizationUser->is_user_organizational = 1;
        $organizationUser->is_user_customer = 0;
        $organizationUser->password = Hash::make($request->password);
        $organizationUser->services = $request->services;

        if ($request->org_id) {
            $organizationUser->org_id = $request->org_id;
        }

        $organizationUser->save();

        // Create relation in the OrganizationalUser table
        $organizationalUser = new OrganizationalUser();
        $organizationalUser->customer_id = $customerUser->id;
        $organizationalUser->user_id = $organizationUser->id;
        $organizationalUser->save();

        // Return a response with the customer data and token
        return response()->json([
            "message" => "Customer and organization user registered successfully.",
            "customer_user" => $customerUser,
            "organization_user" => $organizationUser,
            "token" => $token,
        ], 201);
    }

    public function linkUsers(Request $request)
    {

        $request->validate([
            'customerAdminId' => 'required',
            'organizationalUserId' => 'required',
            'userId' => 'required|exists:users,id',
            'services' => 'required|array',
            'is_user_customer' => 'required',
            'is_user_organizational' => 'required',
        ], [
            'customerAdminId.required' => 'Der Kunden-Admin ist erforderlich.',
            'organizationalUserId.required' => 'Der Organisationsbenutzer ist erforderlich.',
            'userId.required' => 'Die Benutzer-ID ist erforderlich.',
            'userId.exists' => 'Die Benutzer-ID muss in der Benutzertabelle existieren.',
            'services.required' => 'Der Service ist erforderlich.',
            'services.array' => 'Der Service muss ein Array sein.',
            'is_user_customer.required' => 'Das Feld "is_user_customer" ist erforderlich.',
            'is_user_organizational.required' => 'Das Feld "is_user_organizational" ist erforderlich.',
        ]);



        $user = User::find($request->userId);


        if ($request->has('services')) {

            $user->services = $request->services;
        }
        if ($request->has('org_id')) {
            $user->org_id = $request->org_id;
        }
        if ($request->has('is_user_customer')) {
            $user->is_user_customer = $request->is_user_customer;
        }
        if ($request->has('is_user_organizational')) {
            $user->is_user_organizational = $request->is_user_organizational;
        }

        $user->save();



        if ($request->userId) {
            OrganizationalUser::create([
                'customer_id' => $request->customerAdminId,
                'user_id' => $request->organizationalUserId,
                'organizational_id' => $request->userId,
            ]);
            Log::info('User Linked Successfully.');
            Log::info('user id', [$request->user_id]);
        }


        return response()->json([
            "message" => "User Linked Successfully.",
            "user" => $user,

        ], 200);
    }


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $email = $request->email;
        $password = $request->password;
        $translations = Translation::all();
        if (Auth::attempt(['email' => $email, 'password' => $password])) {
            // if (Auth::user()->hasVerifiedEmail()) {
            $user = Auth::user();
            $token = $user->createToken('user_token')->plainTextToken;
            return response()->json([
                "message" => "Logged in successfully",
                "user" => $user,
                "token" => $token,
                "translationData" => $translations,
            ], 200);
            // } else {
            //     return response()->json(["message" => "Your email is not verified."], 422);
            // }
        } else {
            return response()->json(["message" => "invalid_email_or_password"], 422);
        }
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (Hash::check($request->input('old_password'), $user->password)) {
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'message' => 'Password changed successfully',
                'user' => $user,
            ]);
        } else {
            return response()->json([
                'message' => 'Invalid Old password.'
            ], 400);
        }
    }

    public function getuser($id)
    {
        $user = User::with('organization')->findOrFail($id);
        $services_ids = Service::all()->keyBy('id');
        $services = Service::all();
        $orgs = Organization::all();
        if ($user->services) {
            $user->service_names = collect($user->services)->map(function ($serviceId) use ($services_ids) {
                return $services_ids->get($serviceId)->name ?? '';
            })->toArray();
        }

        return response()->json(['user' => $user, 'services' => $services, 'orgs' => $orgs], 200);
    }

    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'services' => 'sometimes|array',
            'email' => 'required|email|unique:users,email,' . $id
        ], [
            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.'
        ]);


        $user = User::findOrFail($id);

        // Update parent user fields
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('password')) {
            $user->password = bcrypt($request->password);
        }
        if ($request->has('services')) {
            $user->services = $request->services;
        }
        if ($request->has('org_id')) {
            $user->org_id = $request->org_id;
        }

        $user->save();

        // Fetch the IDs of the child organizational users (user_id and organizational_id)
        $childUserIds = OrganizationalUser::where('customer_id', $user->id)->pluck('user_id')->toArray();
        $childOrgIds = OrganizationalUser::where('customer_id', $user->id)->pluck('organizational_id')->toArray();

        Log::info('Child User IDs (customer):', [$childUserIds]);
        Log::info('Child Org IDs (customer):', [$childOrgIds]);

        // Fetch the child users (both user_id and organizational_id)
        $childUsers = User::whereIn('id', $childUserIds)->get();
        $childOrgUsers = User::whereIn('id', $childOrgIds)->get();

        // Update each child user (user_id users)
        foreach ($childUsers as $childUser) {
            if ($request->has('services')) {
                $childUser->services = $request->services;
            }
            if ($request->has('org_id')) {
                $childUser->org_id = $request->org_id;
            }
            $childUser->save();
        }

        // Update each organizational user (organizational_id users)
        foreach ($childOrgUsers as $orgUser) {
            if ($request->has('services')) {
                $orgUser->services = $request->services;
            }
            if ($request->has('org_id')) {
                $orgUser->org_id = $request->org_id;
            }
            $orgUser->save();
        }

        return response()->json(['message' => 'User and child organizational users updated successfully', 'user' => $user]);
    }




    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    public function delete($id)
    {
        // Find the user by ID
        $user = User::find($id);

        // Check if the user exists
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Fetch the IDs of the child users and organizational users
        $childUserIds = OrganizationalUser::where('customer_id', $user->id)->pluck('user_id')->toArray();
        $childOrgIds = OrganizationalUser::where('customer_id', $user->id)->pluck('organizational_id')->toArray();

        // Delete child user records (those linked by user_id)
        if (!empty($childUserIds)) {
            User::whereIn('id', $childUserIds)->delete();
        }

        // Delete organizational user records (those linked by organizational_id)
        if (!empty($childOrgIds)) {
            User::whereIn('id', $childOrgIds)->delete();
        }

        // Delete corresponding records in the organizational_user table for both user_id and organizational_id
        OrganizationalUser::where('user_id', $id)->orWhere('organizational_id', $id)->delete();

        // Delete the parent user
        $user->delete();

        return response()->json(['message' => 'User and related records deleted successfully'], 200);
    }


    public function getUserData()
    {
        // Get the currently authenticated user
        $user = Auth::user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Return the user's data with the send_email field
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'send_email' => $user->send_email,


        ]);
    }

    public function getAllOrganizationalUsers()
    {
        // Fetch all users who are organizational users (i.e., 'is_user_organizational' is true)
        $usersInOrganization = User::where('is_user_organizational', true)->get();

        // Get the service IDs from the users (assuming 'services' field contains service IDs)
        $serviceIds = $usersInOrganization->pluck('services')->flatten()->unique();

        // Fetch services based on service IDs, retrieving both id and name
        $services = Service::whereIn('id', $serviceIds)->pluck('name', 'id');

        // Fetch organization names and ids for each user based on their 'org_id'
        $orgIds = $usersInOrganization->pluck('org_id')->unique();
        $organizations = Organization::whereIn('id', $orgIds)->pluck('name', 'id');

        // Map the users and replace the service IDs with service names and include organization names
        $usersWithServiceNames = $usersInOrganization->map(function ($user) use ($services, $organizations) {
            // Get the service names and ids for the user
            $userServices = collect($user->services)->map(function ($serviceId) use ($services) {
                return [
                    'id' => $serviceId,
                    'name' => $services->get($serviceId),
                ];
            });

            // Return the user data with service names and organization name and id
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'services' => $userServices,
                'organization' => [
                    'id' => $user->org_id,
                    'name' => $organizations->get($user->org_id),
                ],
            ];
        });

        // Return the organizational users with service names and organization names
        return response()->json([
            'organization_users' => $usersWithServiceNames,
        ], 200);
    }

    public function getNonOrganizationalUsers()
    {
        // Fetch all users who are organizational users (i.e., 'is_user_organizational' is true)
        $usersInOrganization = User::where('is_user_organizational', null)
            ->where('is_user_customer', null)
            ->where('user_type', 0)
            ->get();

        // Get the service IDs from the users (assuming 'services' field contains service IDs)
        $serviceIds = $usersInOrganization->pluck('services')->flatten()->unique();

        // Fetch services based on service IDs, retrieving both id and name
        $services = Service::whereIn('id', $serviceIds)->pluck('name', 'id');

        // Fetch organization names and ids for each user based on their 'org_id'
        $orgIds = $usersInOrganization->pluck('org_id')->unique();
        $organizations = Organization::whereIn('id', $orgIds)->get();

        // Map the users and replace the service IDs with service names and include organization names
        $usersWithServiceNames = $usersInOrganization->map(function ($user) use ($services, $organizations) {
            // Get the service names and ids for the user
            $userServices = collect($user->services)->map(function ($serviceId) use ($services) {
                return [
                    'id' => $serviceId,
                    'name' => $services->get($serviceId),
                ];
            });

            // Return the user data with service names and organization name and id
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'services' => $userServices,
                'organization' => [
                    'id' => $user->org_id,
                    'name' => $organizations->firstWhere('id', $user->org_id)->name ?? null,
                ],
            ];
        });

        // Return the organizational users with service names and organization names
        return response()->json([
            'all_users' => $usersWithServiceNames,
        ], 200);
    }

    public function organizationalUserWithCustomerAdmins()
    {
        // Fetch all users who are organizational users (i.e., 'is_user_organizational' is true)
        $usersInOrganization = User::with('customerUsers.user')->where('is_user_organizational', true)->get();

        // Get the service IDs from the users (assuming 'services' field contains service IDs)
        $serviceIds = $usersInOrganization->pluck('services')->flatten()->unique();

        // Fetch services based on service IDs, retrieving both id and name
        $services = Service::whereIn('id', $serviceIds)->pluck('name', 'id');

        // Fetch organization names and ids for each user based on their 'org_id'
        $orgIds = $usersInOrganization->pluck('org_id')->unique();
        $organizations = Organization::whereIn('id', $orgIds)->pluck('name', 'id');

        // Map the users and replace the service IDs with service names and include organization names
        $usersWithServiceNames = $usersInOrganization->map(function ($user) use ($services, $organizations) {
            // Get the service names and ids for the user
            $userServices = collect($user->services)->map(function ($serviceId) use ($services) {
                return [
                    'id' => $serviceId,
                    'name' => $services->get($serviceId),
                ];
            });

            // Return the user data with service names and organization name and id
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'services' => $userServices,
                'is_customer' => $user->is_user_customer,
                'customer_admin' => $user->customerUsers,
                'organization' => [
                    'id' => $user->org_id,
                    'name' => $organizations->get($user->org_id),
                ],
            ];
        });

        // Return the organizational users with service names and organization names
        return response()->json([
            'organization_users' => $usersWithServiceNames,
        ], 200);
    }




    private function countToolDocument($organizationId)
    {
        $normalUsers = OrganizationalUser::where('user_id', $organizationId)->pluck('organizational_id')->toArray();

        $dataProcessCount = DataProcess::whereIn('user_id', $normalUsers)->count();
        $documentsCount = Document::whereIn('user_id', $normalUsers)->count();
        $contractSolutionCount = ContractSolutions::whereIn('user_id', $normalUsers)->count();

        $freeDataProcessCount = FreeDataProcess::whereIn('user_id', $normalUsers)->count();
        $allCount = $dataProcessCount + $documentsCount + $contractSolutionCount + $freeDataProcessCount;

        return [
            'dataProcessCount' => $dataProcessCount,
            'documentsCount' => $documentsCount,
            'contractSolutionCount' => $contractSolutionCount,
            'freeDataProcessCount' => $freeDataProcessCount,
            'allCount' => $allCount,
        ];

    }
    public function getAllOrganizationalUsersForCustomer($customerId)
    {
        // Fetch all records from OrganizationalUser where customer_id matches and user_id is different
        $createdUserIds = OrganizationalUser::where('customer_id', $customerId)
            ->where('user_id', '!=', auth()->id()) // Exclude the logged-in user if necessary
            ->pluck('user_id');

        // If there are no users for this customer
        if ($createdUserIds->isEmpty()) {
            return response()->json([
                'message' => 'No users found for this customer.'
            ], 200);
        }

        // Fetch the user details for the users with the provided user IDs
        $usersInOrganization = User::whereIn('id', values: $createdUserIds)->get();


        // Get the service IDs from the users
        $serviceIds = $usersInOrganization->pluck('services')->flatten();

        // Fetch service names based on service IDs
        $serviceNames = Service::whereIn('id', $serviceIds)->pluck('name', 'id');

        // Fetch organization names for each user based on org_id
        $orgIds = $usersInOrganization->pluck('org_id');
        $organizationNames = Organization::whereIn('id', $orgIds)->pluck('name', 'id');



        // Map the users and replace the service IDs with service names and include organization names
        $usersWithServiceNames = $usersInOrganization->map(callback: function ($user) use ($serviceNames, $organizationNames) {
            // Get the service names for the user
            $userServiceNames = collect($user->services)->map(function ($serviceId) use ($serviceNames) {
                return $serviceNames->get($serviceId);
            });


            $documents = $this->countToolDocument($user->id);
            // Return the user data with service names, organization name, and is_user_organizational
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'services' => $userServiceNames,
                'serviceIds' => collect($user->services),
                'dataProcessCount' => $documents['dataProcessCount'],
                'documentsCount' => $documents['documentsCount'],
                'contractSolutionCount' => $documents['contractSolutionCount'],
                'freeDataProcessCount' => $documents['freeDataProcessCount'],
                'allCount' => $documents['allCount'],
                'organization_name' => $organizationNames->get($user->org_id),
                'is_user_organizational' => $user->is_user_organizational, // Add this field

            ];
        });

        // Return the organizational users with service names and organization names
        return response()->json([
            'organization_users' => $usersWithServiceNames,
        ], 200);
    }


}
