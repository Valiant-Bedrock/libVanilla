$outputFile = "libVanilla.phar"
$outputDirectory = "output"

function Log([string] $message) {
	Write-Host "[$(Get-Date)] [*] $($message)"
}

Log "Installing production dependencies..."
# Remove dev dependencies
composer install --no-dev 2>&1 | Out-Null

# Remove file if exists
if (Test-Path $outputDirectory/$outputFile) {
	Log "Found existing output. Deleting file..."
	Remove-Item $outputDirectory/$outputFile
}

$excluded = @(".idea", ".github", "output", ".editorconfig", ".gitattributes", ".gitignore", "LICENSE", "README.md", "build.ps1")

Log "Recreating output directory..."
# Recreate output directory
Remove-Item $outputDirectory -Recurse -ErrorAction Ignore
New-Item -ItemType Directory -Path $outputDirectory 2>&1 | Out-Null

Log "Copying files into output directory..."
# Copy the items into a sub-directory to compile it
ForEach($item in (Get-ChildItem -Path ./ -Exclude $excluded -Name)) {
	# Write-Host "Copying item $($item) to $($outputDirectory)/$($item)"
	Copy-Item -Path $item -Destination $outputDirectory/$item -Recurse 2>&1 | Out-Null
}

Log "Building phar $($outputFile)..."
# Build phar
phar-composer build $outputDirectory $outputDirectory/$outputFile 2>&1 | Out-Null

Log "Removing excessive files..."
# Remove excessive files -- leave phar only
ForEach($item in (Get-ChildItem -Path $outputDirectory -Name)) {
	If(-Not ($item -eq $outputFile)) {
		# Write-Host "Removing item $($item) from $($outputDirectory)"
		Remove-Item $outputDirectory/$item -Recurse -ErrorAction Ignore
	}
}

Log "Reinstalling all dependencies..."
# Reinstall dev dependencies
composer install 2>&1 | Out-Null
Log "Phar built successfully!"