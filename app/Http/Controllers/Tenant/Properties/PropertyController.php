<?php

declare(strict_types=1);
namespace App\Http\Controllers\Tenant\Properties;
use App\Actions\Tenant\Properties\SavePropertyAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Properties\StorePropertyRequest;
use App\Http\Requests\Tenant\Properties\UpdatePropertyRequest;
use App\Models\Tenant\Assignment;
use App\Models\Tenant\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
final class PropertyController extends Controller { public function index(Request $request):View{$q=Property::query()->with('assignment:id,assignment_number')->latest();$q->when($request->assignment_id,fn($q,$v)=>$q->where('assignment_id',$v));return view('tenant.properties.index',['properties'=>$q->paginate(20)->withQueryString(),'assignments'=>Assignment::query()->orderBy('assignment_number')->get(['id','assignment_number'])]);} public function create():View{return view('tenant.properties.create',['property'=>new Property(),'assignments'=>Assignment::query()->orderBy('assignment_number')->get()]);} public function store(StorePropertyRequest $request,SavePropertyAction $action):RedirectResponse{$property=$action->create($request->user('tenant'),$request->validated());return to_route('tenant.properties.show',$property);} public function show(Property $property):View{return view('tenant.properties.show',['property'=>$property->load('assignment','boundaries','owners','units')]);} public function edit(Property $property):View{return view('tenant.properties.edit',['property'=>$property->load('boundaries','owners','units'),'assignments'=>Assignment::query()->orderBy('assignment_number')->get()]);} public function update(UpdatePropertyRequest $request,Property $property,SavePropertyAction $action):RedirectResponse{$action->update($property,$request->user('tenant'),$request->validated());return to_route('tenant.properties.show',$property);} public function toggleStatus(Property $property):RedirectResponse{$property->update(['is_active'=>!$property->is_active]);return back();} }
