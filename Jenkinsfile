pipeline {
    agent any

    stages {
        stage('Checkout') {
            steps {
                // Pastikan workspace bersih agar tidak ada folder hantu bernama .env
                cleanWs()
                checkout scm
            }
        }

        stage('Secure Env Injection') {
            steps {
                // Mengambil file dari Jenkins Credentials (Kind: Secret File)
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_FILE_PATH')]) {
                    script {
                        // Ambil path absolut workspace saat ini
                        def wsPath = pwd()
                        
                        echo "Copying secret file from: ${SECRET_FILE_PATH}"
                        
                        // Perintah shell dengan pengecekan keberadaan file
                        sh """
                            # Hapus jika ada folder .env (penyebab error directory)
                            rm -rf ${wsPath}/.env
                            
                            # Salin file rahasia ke file .env di workspace
                            cp -f '${SECRET_FILE_PATH}' '${wsPath}/.env'
                            
                            # Validasi apakah file benar-benar sudah ada
                            if [ -f '${wsPath}/.env' ]; then
                                echo "SUCCESS: .env has been created in ${wsPath}"
                                chmod 644 '${wsPath}/.env'
                            else
                                echo "ERROR: Failed to create .env file"
                                exit 1
                            fi
                        """
                    }
                }
            }
        }

        stage('Laravel Dependencies') {
            steps {
                // Menjalankan perintah Laravel menggunakan file .env yang sudah siap
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'
                
                // Jika .env hasil copy tidak punya key, kita generate
                sh 'php artisan key:generate --force'
            }
        }

        stage('Execute Tests') {
            steps {
                echo 'Running Drama Box Auth Test Suite...'
                sh 'php artisan test'
            }
        }
    }

    post {
        always {
            // Hapus file sensitif setelah build selesai demi keamanan
            sh 'rm -f .env'
        }
    }
}
