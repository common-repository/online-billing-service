DEBUG = nil

if ARGV.size != 2
  puts "Usage: ruby #{__FILE__} <featureset> <destination_path>\nExiting ..."
  exit
end

feature_set = ARGV[0]
new_path_for_plugin = "#{ARGV[1]}/".gsub(/\/\/$/, '/')

unless %w(f4 obs).include?(feature_set)
  puts "Usage: ruby #{__FILE__} <featureset> <destination_path>\n"
  puts "FeatureSet #{feature_set} not found.\nExiting ..."
  exit
end


puts "Running deploy script for featureset #{feature_set}. Destination path #{new_path_for_plugin}"

settings_plugin_identities  = 
  if feature_set == 'f4'
    {
      '{{PLUGIN_IDENTITY_NAME_UPPERCASE}}' => 'FACTUREAZA',
      '{{PLUGIN_IDENTITY_LABEL_UPPERCASE}}' => 'F4',
      '{{PLUGIN_IDENTITY_NAME_LOWERCASE}}' => 'factureaza',
      '{{PLUGIN_IDENTITY_LABEL_LOWERCASE}}' => 'f4',
      '{{PLUGIN_IDENTITY_LINK}}' => 'factureaza.ro',
      '{{PLUGIN_IDENTITY_API_ENDPOINT}}' => 'https://factureaza.ro',
      '{{PLUGIN_DOWNLOAD_LINK}}' => 'https://factureaza.ro',
      '{{PLUGIN_IDENTITY_MAIL_ADDRESS}}' => 'office@factureaza.ro',
      '{{PLUGIN_SHARED_DOCUMENT_TLD}}' => 'vizualizare'
    }
  elsif feature_set == 'obs'
    {
      '{{PLUGIN_IDENTITY_NAME_UPPERCASE}}' => 'ONLINE BILLING SERVICE',
      '{{PLUGIN_IDENTITY_LABEL_UPPERCASE}}' => 'OBS',
      '{{PLUGIN_IDENTITY_NAME_LOWERCASE}}' => 'online billing service',
      '{{PLUGIN_IDENTITY_LABEL_LOWERCASE}}' => 'obs',
      '{{PLUGIN_IDENTITY_LINK}}' => 'online-billing-service.com',
      '{{PLUGIN_IDENTITY_API_ENDPOINT}}' => 'https://online-billing-service.com',
      '{{PLUGIN_DOWNLOAD_LINK}}' => 'https://online-billing-service.com',
      '{{PLUGIN_IDENTITY_MAIL_ADDRESS}}' => 'office@online-billing-service.com',
      '{{PLUGIN_SHARED_DOCUMENT_TLD}}' => 'view'
   }
  end

unless new_path_for_plugin.to_s.strip.empty?
  unless File.exist? File.expand_path(new_path_for_plugin)
    `mkdir #{new_path_for_plugin}`
  end
  
  `cp -r * #{new_path_for_plugin}`
end

settings_plugin_identities.each do |settings_plugin_identity_template, settings_plugin_identity_value|
  puts "Replacing #{settings_plugin_identity_template} => #{settings_plugin_identity_value}" if DEBUG
  changing_setting_path = `grep -rnl "#{new_path_for_plugin}" -e #{settings_plugin_identity_template}`
  changing_setting_path = changing_setting_path.split("\n")

  # remove script file from array 
  changing_setting_path.each do |file|
    if file =~ /\.rb$/
      changing_setting_path.delete(file)
    end
  end

  changing_setting_path.each do |path|
    puts "Replacing in file #{path}" if DEBUG
    text = File.read(path)
    text.gsub!(settings_plugin_identity_template, settings_plugin_identity_value)

    File.open(path, "w") {|file| file.puts text }
  end
   
  # changing_setting_path.each do |path|
  #   puts "Replacing errors (PLUGIN_IDENTITY_LABEL_LOWERCASE}}) in file #{path}" if DEBUG
  #   text = File.read(path)

  #   text.gsub!('PLUGIN_IDENTITY_LABEL_LOWERCASE}}', '')
  #   File.open(path, "w") {|file| file.puts text }
  # end

  # # changing_setting_path.each do |path|
  # #   puts "Replacing errors (PLUGIN_IDENTITY_LABEL_UPPERCASE}}) in file #{path}" if DEBUG
  # #   text = File.read(path)

  # #   text.gsub!('PLUGIN_IDENTITY_LABEL_UPPERCASE}}', '')
  # #   File.open(path, "w") {|file| file.puts text }
  # # end
end

if feature_set == 'f4'
  `cd "#{new_path_for_plugin}"; mv f4README.txt README.txt; rm -rf obsREADME.txt;`
elsif feature_set == 'obs'
  `cd "#{new_path_for_plugin}"; mv obsREADME.txt README.txt; rm -rf f4README.txt;`
end

`cd "#{new_path_for_plugin}"; rm -rf deploy-f4-wp.rb;`

unless new_path_for_plugin.to_s.strip.empty?
  Dir.chdir(new_path_for_plugin)
end

files = Dir.glob("**/*")

files.each do |file|
    if file =~ /\.rb$/
        files.delete(file)
    end

    settings_plugin_identities.each do |settings_plugin_identity_template, settings_plugin_identity_value|
      if file.include?(settings_plugin_identity_template)
          new_file_name = file.gsub(settings_plugin_identity_template, settings_plugin_identity_value)
          File.rename(file, new_file_name)
      end
    end
end

# Solve some errors
['PLUGIN_IDENTITY_LABEL_LOWERCASE}}', 'PLUGIN_IDENTITY_LABEL_UPPERCASE}}'].each do |error|
  errors_path = `grep -rnl "#{new_path_for_plugin}" -e #{error}`
  errors_path = errors_path.split("\n")

  # remove script file from array 
  errors_path.each do |file|
    if file =~ /\.rb$/
      errors_path.delete(file)
    end
  end

  errors_path.each do |path|
    puts "Replacing error in file #{path}" 
    text = File.read(path)
    text.gsub!(error, '')

    File.open(path, "w") {|file| file.puts text }
  end
end
