<!-- ============ MOBILE MENU ============ -->
<div class="menu" id="menu" hidden role="dialog" aria-modal="true" aria-label="Menu">
  <div class="wrap">
    <div class="top">
      <span class="logo">
        <svg width="30" height="30" viewBox="0 0 30 30" aria-hidden="true"><rect width="30" height="30" rx="7" fill="#fff"/><path d="M9 19.6c.8 1.9 3.2 2.9 6.2 2.9 3.6 0 5.8-1.5 5.8-3.6 0-4.9-11.6-2.7-11.6-7.6 0-2 2.2-3.3 5.4-3.3 2.6 0 4.6.9 5.4 2.6" fill="none" stroke="#2A2F9E" stroke-width="2.6" stroke-linecap="round"/></svg>
        <b>School<span>Runs</span></b>
      </span>
      <button class="burger" id="closeMenu" aria-label="Close menu">
        <svg width="22" height="22" viewBox="0 0 22 22" aria-hidden="true"><path d="M2 2l18 18M20 2L2 20" stroke="currentColor" stroke-width="2.4"/></svg>
      </button>
    </div>
    <ul>
      <li><a class="m-item" href="#features" data-close>About Us</a></li>
      <li>
        <button class="m-item" id="prodToggle" aria-expanded="false" aria-controls="prodSub">Products
          <svg width="18" height="11" viewBox="0 0 20 12" aria-hidden="true"><path d="M2 2l8 8 8-8" fill="none" stroke="currentColor" stroke-width="2.4"/></svg>
        </button>
        <div class="m-sub" id="prodSub" hidden>
          <div class="m-group">
            <div class="m-label">Finance &amp; Payments</div>
            <a href="#features" data-close>Online Fee Payments</a>
            <a href="#features" data-close>Billing &amp; Invoicing</a>
          </div>
          <div class="m-group">
            <div class="m-label">AI Teaching Suite</div>
            <a href="#features" data-close>Lesson Note Generator</a>
            <a href="#features" data-close>Exam Question Bank</a>
          </div>
          <div class="m-group">
            <div class="m-label">Portals</div>
            <a href="#features" data-close>Parents</a>
            <a href="#features" data-close>Students</a>
            <a href="#features" data-close>Staff &amp; HR</a>
          </div>
          <div class="m-group">
            <div class="m-label">School Operations</div>
            <a href="#features" data-close>Results &amp; Report Cards</a>
            <a href="#features" data-close>Attendance</a>
            <a href="#features" data-close>Admissions</a>
            <a href="#features" data-close>Library, Transport &amp; Hostel</a>
          </div>
        </div>
      </li>
      <li><a class="m-item" href="#how" data-close>How it works</a></li>
            <li><a class="m-item" href="{{ route('login') }}" data-close>Log in</a></li>
    </ul>
    <a href="{{ route('register') }}" class="btn btn-white" data-close>Register your school</a>
  </div>
</div>

