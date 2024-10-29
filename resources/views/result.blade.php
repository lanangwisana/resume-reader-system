<!DOCTYPE html>
<html>
<head>
    <title>Extracted Text</title>
</head>
<body>    
    <h2>Extracted Text from PDF</h2>
    @if(isset($errors) && count($errors) > 0)
    <div class="alert alert-danger">
        <ul>
            @foreach($errors as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(isset($success))
        <div class="alert alert-success">
            {{ $success }}
        </div>
    @endif
    <pre>{{ $text }}</pre>
</body>
</html>
