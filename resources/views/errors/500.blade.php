@extends('errors.app')

@section('title')
    Sing In
@endsection

@section('main')
    <div class="px-3">
        <div class="row min-vh-100 flex-center p-5">
            <div class="col-12 col-xl-10 col-xxl-8">
                <div class="row justify-content-center g-5">
                    <div class="col-12 col-lg-6 text-center order-lg-1"><img class="img-fluid w-lg-100 d-light-none"
                            src="{{ asset('assets/img/spot-illustrations/500-illustration.png') }}" alt=""
                            width="400" /><img class="img-fluid w-md-50 w-lg-100 d-dark-none"
                            src="{{ asset('assets/img/spot-illustrations/dark_500-illustration.png') }}" alt=""
                            width="540" /></div>
                    <div class="col-12 col-lg-6 text-center text-lg-start"><img
                            class="img-fluid mb-6 w-50 w-lg-75 d-dark-none"
                            src="{{ asset('assets/img/spot-illustrations/500.png') }}" alt="" /><img
                            class="img-fluid mb-6 w-50 w-lg-75 d-light-none"
                            src="{{ asset('assets/img/spot-illustrations/dark_500.png') }}" alt="" />
                        <h2 class="text-body-secondary fw-bolder mb-3">Unknow error!</h2>
                        <p class="text-body mb-5">But relax! Our cat is here to play you some music.</p><a
                            class="btn btn-lg btn-primary" href="{{route('admin.home.index')}}">Go Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
