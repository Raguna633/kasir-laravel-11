<footer class="main-footer">
    <div class="pull-right hidden-xs">
        <b>Version</b> 1.2
    </div>
    <div class="logo">
        <img src="{{ asset('img/tefatie.png') }}" alt="Company Logo" class="company-logo">
    </div>
    <strong>Copyright &copy; {{ date('Y') }} <a href="/">{{ $setting->nama_perusahaan }}</a>.</strong> All
    rights
    reserved.
</footer>

<style>
    .navbar-logo {

        position: absolute;
        left: 50%;
        top: 50%
        transform: translateX(-50%);
        z-index: 1; /* Ensure it appears above other elements if needed */
    }

    .company-logo {
        height: 40px; /* Adjust logo height */
        max-width: 100%;
    }
</style>