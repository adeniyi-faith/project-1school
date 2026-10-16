{{-- The hidden parts of every public form: the trap box and the signed ticket (see App\Website\Visitor). Needs $school and $kind. --}}
<div class="live-hp" aria-hidden="true"><label>Leave this empty <input type="text" name="{{ \App\Website\Visitor::TRAP }}" tabindex="-1" autocomplete="off"></label></div>
<input type="hidden" name="{{ \App\Website\Visitor::TICKET }}" value="{{ \App\Website\Visitor::ticket($school->id, $kind) }}">
