<!-- Features: Start -->
@php
$i = 0;
$bg = "bg-body";
@endphp
@foreach ($features as $feature)
@php
if ($i > 0) {
  if ($bg == "") {
    $bg = "bg-body";
  } else {
    $bg = "";
  }
}
$i++;
@endphp
<section class="section-py {{ $bg }} b-articles" data-aos="fade-up" data-aos-duration="1000">
  <div class="container">
    <div class="row gy-4 mb-4">
        <div class="col-lg-6 p-5">
          <img
            class="img-fluid"
            src="{{ asset($feature->image) }}" alt="{{ $feature->title }}"
          />
        </div>
        <div class="col-lg-6">
          <h1>{{ $feature->title }}</h1>
          <p>{{ $feature->content }}</p>
        </div>
    </div>
  </div>
</section>
@endforeach
