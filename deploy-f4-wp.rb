require 'pry'
if ARGV.size != 1
  puts "Usage: ruby #{__FILE__} <factureaza4_path>\nExiting ..."
  exit
end
  
factureaza4_path = ARGV[0]
current_path = File.expand_path(File.dirname(__FILE__))

system('rm -rf factureaza; rm -rf src_f4_woocommerce; rm -rf f4.zip;') 
system('svn co http://plugins.svn.wordpress.org/factureaza/')
system("cd #{factureaza4_path}/public/integrations/woocommerce/; cp -R f4.zip #{current_path}/")
system("cd #{current_path}; unzip f4.zip")
system('cd src_f4_woocommerce; cp -R * ../factureaza/trunk')
system('cd factureaza; svn add trunk/*;')
system("cd factureaza; svn ci -m 'Automated new release';")
system('rm -rf factureaza; rm -rf src_f4_woocommerce; rm -rf f4.zip;') 