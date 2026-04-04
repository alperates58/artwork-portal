<p>Sayın {{ $user->name }},</p>

<p>{{ $brandName }}'a hoş geldiniz!</p>

<p>Tedarikçi kayıt talebiniz onaylanmıştır. Aşağıdaki bilgileri kullanarak portala giriş yapabilirsiniz.</p>

<ul>
    <li><strong>Firma:</strong> {{ $registration->company_name }}</li>
    <li><strong>Kullanıcı adı (e-posta):</strong> {{ $user->email }}</li>
</ul>

<p>
    Portala giriş yapmak için:<br>
    <a href="{{ $loginUrl }}">{{ $loginUrl }}</a>
</p>

<p>Giriş yaptıktan sonra profilinizden şifrenizi değiştirebilirsiniz.</p>

<p>Herhangi bir sorunuz olursa sistem yöneticiniz ile iletişime geçebilirsiniz.</p>

<p>Bu bildirim {{ $brandName }} tarafından otomatik oluşturulmuştur.</p>
