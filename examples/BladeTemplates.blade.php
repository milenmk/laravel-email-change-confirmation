{{-- 
Example Blade templates showing how to integrate the package in traditional Laravel views
--}}

{{-- Profile Edit Form (resources/views/profile/edit.blade.php) --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Profile Settings') }}</div>

                <div class="card-body">
                    {{-- Success Messages --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Email Change Notification --}}
                    @if (session('email-change-notification'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-envelope me-2"></i>
                            {{ session('email-change-notification') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Pending Email Change Alert --}}
                    @if (auth()->user()->hasPendingEmailChange())
                        @php $pendingChange = auth()->user()->getLatestPendingEmailChange(); @endphp
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-clock me-2"></i>
                            <strong>Pending Email Change</strong><br>
                            You have requested to change your email to <strong>{{ $pendingChange->new_email }}</strong>.<br>
                            Please check your current email address ({{ $pendingChange->current_email }}) for confirmation instructions.
                            <hr>
                            <form method="POST" action="{{ route('email-change-confirmation.cancel-pending') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    Cancel Request
                                </button>
                            </form>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('Name') }}</label>
                            <input id="name" type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   name="name" 
                                   value="{{ old('name', auth()->user()->name) }}" 
                                   required>
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('Email Address') }}</label>
                            <input id="email" type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   name="email" 
                                   value="{{ old('email', auth()->user()->email) }}" 
                                   required>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <div class="form-text">
                                Changing your email address will require confirmation from your current email.
                            </div>
                        </div>

                        <div class="mb-0">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Update Profile') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Pending Email Changes View (resources/views/profile/pending-email-changes.blade.php) --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('Pending Email Changes') }}</span>
                    <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-secondary">
                        {{ __('Back to Profile') }}
                    </a>
                </div>

                <div class="card-body">
                    @if ($pendingChanges->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <h5>{{ __('No Pending Email Changes') }}</h5>
                            <p class="text-muted">{{ __('You have no pending email change requests.') }}</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('Current Email') }}</th>
                                        <th>{{ __('New Email') }}</th>
                                        <th>{{ __('Requested At') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pendingChanges as $change)
                                        <tr>
                                            <td>{{ $change->current_email }}</td>
                                            <td>{{ $change->new_email }}</td>
                                            <td>{{ $change->created_at->format('M j, Y g:i A') }}</td>
                                            <td>
                                                <span class="badge bg-warning">
                                                    {{ __('Pending Confirmation') }}
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" 
                                                      action="{{ route('email-change-confirmation.cancel-pending') }}" 
                                                      class="d-inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to cancel this email change request?')">
                                                        {{ __('Cancel') }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <form method="POST" action="{{ route('email-change-confirmation.cancel-pending') }}">
                                @csrf
                                <button type="submit" 
                                        class="btn btn-danger"
                                        onclick="return confirm('Are you sure you want to cancel ALL pending email change requests?')">
                                    {{ __('Cancel All Pending Changes') }}
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Email Change Success Page (resources/views/email-change/success.blade.php) --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-check-circle text-success fa-4x mb-4"></i>
                    <h3 class="mb-3">{{ __('Email Change Confirmed!') }}</h3>
                    <p class="text-muted mb-4">
                        {{ __('Your email address has been successfully updated.') }}
                    </p>
                    
                    @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && is_null(auth()->user()->email_verified_at))
                        <div class="alert alert-info">
                            <i class="fas fa-envelope me-2"></i>
                            {{ __('Please check your new email address for a verification link.') }}
                        </div>
                        <a href="{{ route('verification.notice') }}" class="btn btn-primary">
                            {{ __('Go to Email Verification') }}
                        </a>
                    @else
                        <a href="{{ route('profile.edit') }}" class="btn btn-primary">
                            {{ __('Back to Profile') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- JavaScript for handling notifications --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-warning)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Handle email change form submission
    const emailInput = document.getElementById('email');
    const originalEmail = emailInput.value;
    
    emailInput.addEventListener('change', function() {
        if (this.value !== originalEmail) {
            const helpText = this.parentNode.querySelector('.form-text');
            helpText.innerHTML = '<i class="fas fa-info-circle me-1"></i>Changing your email will require confirmation from your current email address.';
            helpText.classList.add('text-info');
        }
    });
});
</script>
@endpush