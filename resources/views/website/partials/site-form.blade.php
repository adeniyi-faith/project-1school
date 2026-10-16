{{-- A custom form built in Website > Forms. Needs $form (SiteForm) and $school. $offer (show the newsletter tick) is worked out when not given. --}}
@php
    $bag = $errors->getBag('form'.$form->id);
    $done = session('form_done')[$form->id] ?? null;
    $offer = $offer ?? app(\App\Forms\FormService::class)->newsletterOffered($school, $form);
    $fid = 'sf'.$form->id;
    $input = ['text' => 'text', 'email' => 'email', 'phone' => 'tel', 'number' => 'number', 'date' => 'date'];
    $link = fn (string $text) => ! empty($privacyUrl) && str_contains($text, 'Privacy Policy')
        ? \Illuminate\Support\Str::replaceFirst('Privacy Policy', '<a href="'.e($privacyUrl).'">Privacy Policy</a>', e($text)) : e($text);
@endphp
<div class="sf sf-{{ $form->id }}" id="form-{{ $form->id }}">
    @if ($done)
        <p class="live-ok" role="status">{{ $done }}</p>
    @else
        <form class="sf-form" method="post" action="{{ route('site.form.submit', [$school->slug, $form->id]) }}" @if (collect($form->fields)->contains('type', 'file')) enctype="multipart/form-data" @endif data-sf>
            @csrf
            @include('website.partials.guard', ['school' => $school, 'kind' => 'form-'.$form->id])
            <div class="sf-grid">
                @foreach ($form->fields as $f)
                    @php
                        $id = $fid.'-'.$f['key']; $name = 'a['.$f['key'].']'; $old = old('a.'.$f['key']); $err = $bag->first('a.'.$f['key']) ?: $bag->first('a.'.$f['key'].'.*');
                        $help = $f['help'] !== '' ? $id.'-help' : null;
                    @endphp
                    <div class="sf-field sf-field--{{ $f['type'] }}{{ $f['half'] ? ' sf-field--half' : '' }}" data-field="{{ $f['key'] }}"
                        @if (! empty($f['show_if'])) data-if-key="{{ $f['show_if']['key'] }}" data-if-value="{{ $f['show_if']['value'] }}" @endif>
                        @if (in_array($f['type'], ['radio', 'checkboxes'], true))
                            <fieldset @if ($help) aria-describedby="{{ $help }}" @endif>
                                <legend>{{ $f['label'] }}@if ($f['required']) <em aria-hidden="true">*</em>@endif</legend>
                                @foreach ($f['options'] as $n => $opt)
                                    <label class="sf-choice">
                                        @if ($f['type'] === 'radio')
                                            <input type="radio" name="{{ $name }}" value="{{ $opt }}" @checked($old === $opt) @required($f['required'] && $n === 0)>
                                        @else
                                            <input type="checkbox" name="{{ $name }}[]" value="{{ $opt }}" @checked(in_array($opt, (array) $old, true))>
                                        @endif
                                        <span>{{ $opt }}</span>
                                    </label>
                                @endforeach
                            </fieldset>
                        @elseif ($f['type'] === 'tick')
                            <label class="sf-choice sf-tick"><input type="checkbox" name="{{ $name }}" value="1" @checked($old) @required($f['required'])><span>{{ $f['label'] }}@if ($f['required']) <em aria-hidden="true">*</em>@endif</span></label>
                        @else
                            <label for="{{ $id }}">{{ $f['label'] }}@if ($f['required']) <em aria-hidden="true">*</em>@endif</label>
                            @if ($f['type'] === 'file')
                                <input id="{{ $id }}" name="{{ $name }}" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" @required($f['required']) @if ($help) aria-describedby="{{ $help }}" @endif>
                                <small class="sf-help">PDF, Word, JPG or PNG, up to {{ \App\Forms\FormSchema::FILE_KB / 1024 }} MB.</small>
                            @elseif ($f['type'] === 'textarea')
                                <textarea id="{{ $id }}" name="{{ $name }}" rows="5" maxlength="2000" placeholder="{{ $f['placeholder'] }}" @required($f['required']) @if ($help) aria-describedby="{{ $help }}" @endif>{{ $old }}</textarea>
                            @elseif ($f['type'] === 'select')
                                <select id="{{ $id }}" name="{{ $name }}" @required($f['required']) @if ($help) aria-describedby="{{ $help }}" @endif>
                                    <option value="">Choose one</option>
                                    @foreach ($f['options'] as $opt)<option value="{{ $opt }}" @selected($old === $opt)>{{ $opt }}</option>@endforeach
                                </select>
                            @else
                                <input id="{{ $id }}" name="{{ $name }}" type="{{ $input[$f['type']] }}" value="{{ $old }}" maxlength="200" placeholder="{{ $f['placeholder'] }}" @required($f['required'])
                                    @if ($f['type'] === 'number') step="any" inputmode="decimal" @elseif ($f['type'] === 'phone') inputmode="tel" autocomplete="tel" @elseif ($f['type'] === 'email') autocomplete="email" @endif
                                    @if ($help) aria-describedby="{{ $help }}" @endif>
                            @endif
                        @endif
                        @if ($help)<small class="sf-help" id="{{ $help }}">{{ $f['help'] }}</small>@endif
                        @if ($err)<small class="sf-err" role="alert">{{ $err }}</small>@endif
                    </div>
                @endforeach
            </div>

            @if (count($form->consents ?? []) || $offer)
                <div class="sf-consents">
                    @foreach ($form->consents ?? [] as $i => $c)
                        <label class="sf-choice sf-consent"><input type="checkbox" name="c[{{ $i }}]" value="1" @checked(old('c.'.$i)) @required($c['required'])><span>{!! $link($c['text']) !!}@if ($c['required']) <em aria-hidden="true">*</em>@endif</span></label>
                        @if ($bag->first('c.'.$i))<small class="sf-err" role="alert">{{ $bag->first('c.'.$i) }}</small>@endif
                    @endforeach
                    @if ($offer)
                        <label class="sf-choice sf-consent"><input type="checkbox" name="join_newsletter" value="1" @checked(old('join_newsletter'))><span>Also send me the school newsletter. We will email you to confirm first.</span></label>
                    @endif
                </div>
            @endif

            @if ($bag->first('form'))<p class="live-err" role="alert">{{ $bag->first('form') }}</p>@endif
            @if ($bag->any() && ! $bag->first('form'))<p class="live-err" role="alert">Please check the questions marked below.</p>@endif
            <button class="btn btn--solid" type="submit">{{ $form->button_text ?: 'Send' }}</button>
        </form>
    @endif
</div>
@once
<script>
(function(){
  // questions that only appear after another answer: hide them (and stop them being sent) until the answer is picked
  document.querySelectorAll('form[data-sf]').forEach(function(form){
    var fields=form.querySelectorAll('[data-if-key]');
    if(!fields.length)return;
    function answer(key){
      var box=form.querySelector('[data-field="'+key+'"]');
      if(!box||box.hidden)return null;
      var inputs=box.querySelectorAll('input,select');var out=[];
      inputs.forEach(function(i){
        if(i.type==='checkbox'||i.type==='radio'){if(i.checked)out.push(i.value);}
        else if(i.tagName==='SELECT'){if(i.value)out.push(i.value);}
      });
      return out;
    }
    function apply(){
      fields.forEach(function(f){
        var a=answer(f.getAttribute('data-if-key')),want=f.getAttribute('data-if-value');
        var control=form.querySelector('[data-field="'+f.getAttribute('data-if-key')+'"]');
        var isTick=control&&control.classList.contains('sf-field--tick');
        var show=a!==null&&(isTick?a.length>0:a.indexOf(want)!==-1);
        f.hidden=!show;
        f.querySelectorAll('input,select,textarea').forEach(function(i){i.disabled=!show;});
      });
    }
    form.addEventListener('change',apply);apply();
  });
})();
</script>
@endonce
