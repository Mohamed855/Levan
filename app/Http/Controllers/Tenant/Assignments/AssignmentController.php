<?php

declare(strict_types=1);
namespace App\Http\Controllers\Tenant\Assignments;
use App\Actions\Tenant\Assignments\SaveAssignmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Assignments\StoreAssignmentRequest;
use App\Http\Requests\Tenant\Assignments\UpdateAssignmentRequest;
use App\Models\Tenant\Assignment;
use App\Models\Tenant\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
final class AssignmentController extends Controller { public function index(Request $request): View { $q=Assignment::query()->withCount(['properties','auctions'])->latest(); $q->when($request->status,fn($q,$v)=>$q->where('status',$v)); return view('tenant.assignments.index',['assignments'=>$q->paginate(20)->withQueryString()]); } public function create(): View { return view('tenant.assignments.create',['assignment'=>new Assignment(),'properties'=>Property::query()->whereNull('assignment_id')->where('is_active',true)->get()]); } public function store(StoreAssignmentRequest $request,SaveAssignmentAction $action): RedirectResponse { $assignment=$action->create($request->user('tenant'),$request->validated()); return to_route('tenant.assignments.show',$assignment); } public function show(Assignment $assignment): View { return view('tenant.assignments.show',['assignment'=>$assignment->load('properties','auctions')]); } public function edit(Assignment $assignment): View { return view('tenant.assignments.edit',['assignment'=>$assignment->load('properties'),'properties'=>Property::query()->where(fn($q)=>$q->whereNull('assignment_id')->orWhere('assignment_id',$assignment->id))->get()]); } public function update(UpdateAssignmentRequest $request,Assignment $assignment,SaveAssignmentAction $action): RedirectResponse { $action->update($assignment,$request->user('tenant'),$request->validated()); return to_route('tenant.assignments.show',$assignment); } public function toggleStatus(Assignment $assignment): RedirectResponse { $assignment->update(['is_active'=>!$assignment->is_active]); return back(); } }
