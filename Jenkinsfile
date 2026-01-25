pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '5'))
        timeout(time: 15, unit: 'MINUTES')
        disableConcurrentBuilds()
    }

    stages {
        stage('Checkout') {
            steps {
                // Menarik kode terbaru dari repositori drama-box-auth
                checkout scm
            }
        }

        stage('Setup Runtime (PHP 8.4) & .env') {
            steps {
                echo 'Mencari dan mengunduh PHP 8.4 Portable terbaru...'
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // 1. Setup .env (Menangani folder dengan spasi)
                        sh 'rm -rf .env'
                        sh 'cat "${SECRET_ENV}" > .env'

                        // 2. Deteksi link PHP 8.4 secara dinamis via GitHub API
                        sh '''
                            mkdir -p local_bin
                            echo "Mengambil info release terbaru dari GitHub..."
                            
                            # Mengambil link download untuk PHP 8.4 (Linux x86_64 CLI)
                            LATEST_JSON=$(curl -sk https://api.github.com/repos/crazywhalecc/static-php-cli/releases/latest)
                            DOWNLOAD_URL=$(echo "$LATEST_JSON" | grep "browser_download_url" | grep "php-8.4" | grep "cli-linux-x86_64.tar.gz" | cut -d '"' -f 4 | head -n 1)

                            if [ -z "$DOWNLOAD_URL" ]; then
                                echo "Gagal mendapatkan link .tar.gz, mencoba link binary tunggal (micro)..."
                                DOWNLOAD_URL=$(echo "$LATEST_JSON" | grep "browser_download_url" | grep "php-8.4" | grep "micro-linux-x86_64" | cut -d '"' -f 4 | head -n 1)
                            fi

                            if [ -z "$DOWNLOAD_URL" ]; then
                                echo "FATAL ERROR: Link PHP 8.4 tidak ditemukan di GitHub!"
                                exit 1
                            fi

                            echo "Link ditemukan: $DOWNLOAD_URL"
                            curl -Lk "$DOWNLOAD_URL" -o php_package
                            
                            # Ekstrak file atau pindahkan jika itu binary mentah
                            if file php_package | grep -q 'gzip compressed data'; then
                                tar -xzf php_package -C local_bin/
                                # Standarisasi nama binary menjadi 'php'
                                find local_bin -name "php*" -type f -exec mv {} local_bin/php \\;
                            else
                                mv php_package local_bin/php
                            fi

                            chmod +x local_bin/php
                            echo "Verifikasi Versi:"
                            ./local_bin/php -v
                        '''
                    }
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                echo 'Installing Composer & Laravel Packages...'
                sh '''
                    # Download composer terbaru
                    curl -Lk https://getcomposer.org/composer-stable.phar -o composer.phar
                    
                    # Jalankan install menggunakan PHP 8.4 lokal
                    # --ignore-platform-reqs digunakan karena environment Jenkins sangat minimal
                    ./local_bin/php composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs
                '''
            }
        }

        stage('Laravel Preparation') {
            steps {
                echo 'Optimizing Laravel Application...'
                sh '''
                    ./local_bin/php artisan key:generate --force
                    ./local_bin/php artisan storage:link
                    
                    # Jalankan migrasi database (opsional, abaikan jika gagal)
                    ./local_bin/php artisan migrate --force || echo "Migrasi gagal atau database belum siap."
                    
                    # Laravel Caching
                    ./local_bin/php artisan config:cache
                    ./local_bin/php artisan route:cache
                    ./local_bin/php artisan view:cache
                    
                    # Fix Permission folder storage (abaikan jika ditolak)
                    chmod -R 775 storage bootstrap/cache || true
                '''
            }
        }
    }

    post {
        always {
            echo 'Membersihkan file temporary...'
            sh 'rm -f php_package composer.phar'
        }
        success {
            echo '============================================='
            echo ' DEPLOY DRAMA-BOX-AUTH (PHP 8.4) SUCCESSFUL! '
            echo '============================================='
        }
        failure {
            echo 'Deployment failed. Cek log di atas untuk detail error.'
        }
    }
}
