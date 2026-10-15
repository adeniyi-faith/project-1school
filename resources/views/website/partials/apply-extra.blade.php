{{-- The child's gender and birth date, in the look of whichever theme's apply form includes this ($fc = that theme's field class, $sr = label hidden, placeholder shown) --}}
<label class="{{ $fc }}"><span @if ($sr ?? false) class="sr" @endif>Child's gender (optional)</span>
    <select name="student_gender">
        <option value="">{{ ($sr ?? false) ? "Child's gender (optional)" : 'Choose' }}</option>
        <option value="male" @selected(old('student_gender') === 'male')>Boy</option>
        <option value="female" @selected(old('student_gender') === 'female')>Girl</option>
    </select>@error('student_gender')<small class="err">{{ $message }}</small>@enderror</label>
<label class="{{ $fc }}"><span @if ($sr ?? false) class="sr" @endif>Child's date of birth (optional)</span>
    <input type="date" name="student_dob" value="{{ old('student_dob') }}" max="{{ now()->toDateString() }}" @if ($sr ?? false) placeholder="Child's date of birth (optional)" @endif>@error('student_dob')<small class="err">{{ $message }}</small>@enderror</label>
