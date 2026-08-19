@extends('website.layouts.master')

@section('title', __('Rentdo - Your Dream Property Awaits'))

@section('content')
    @include('website.sections.hero')
    @include('website.sections.featured-categories')
    @include('website.sections.featured-properties')
    @include('website.sections.why-choose-readyrental')
    @include('website.sections.property-by-location')
    @include('website.sections.new-projects')
    @include('website.sections.agents-agencies')
    @include('website.sections.app-download-banner')
    @include('website.sections.testimonials')
    @include('website.sections.latest-from-blog')
@endsection
