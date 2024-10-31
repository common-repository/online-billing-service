if ARGV.size != 2
  puts "Usage: ruby #{__FILE__} <featureset> <destination_path>\nExiting ..."
  exit
end

feature_set = ARGV[0]
app_folder_name = ARGV[1]

`ruby deploy.rb #{feature_set} src_#{feature_set}_woocommerce`
if File.directory?(target_dir = "../#{app_folder_name}/spec/resources/woocommerce_dump/wordpress/wp-content/plugins/")
  `cd #{target_dir}; rm -rf src_#{feature_set}_woocommerce`
  `cp -r src_#{feature_set}_woocommerce #{target_dir}`
end
`zip -r #{feature_set}.zip src_#{feature_set}_woocommerce/`
`mv #{feature_set}.zip ../#{app_folder_name}/public/integrations/woocommerce/`

`rm -rf src_#{feature_set}_woocommerce`
`rm -rf #{feature_set}.zip`
