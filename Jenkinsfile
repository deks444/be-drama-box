pipeline {
    agent any

    stages {
        stage('Checkout') {
            steps {
                cleanWs()
                checkout scm
            }
        }

        stage('Secure Env Injection') {
            steps {
                // Menggunakan withCredentials untuk menghindari warning Groovy Interpolation
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_FILE')]) {
                    script {
                        // Gunakan single quotes (') pada sh untuk keamanan total
                        // Kita gunakan double quotes pada variable shell ("$SECRET_FILE") 
                        // untuk menangani spasi pada path workspace
                        sh '''
                            # Hapus jika ada folder/file .env lama
                            rm -rf .env
                            
                            # Salin file menggunakan variable environment shell asli
                            # Tambahkan tanda kutip pada target agar folder berspasi aman
                            cp -f "$SECRET_FILE" .env
                            
                            # Validasi tanpa menampilkan path rahasia di echo
                            if [ -f .env ]; then
                                chmod 644 .env
                                echo "File .env configured successfully."
                            else
                                echo "Failed to inject .env file."
                                exit 1
                            fi
                        '''
                    }
                }
            }
        }

        stage('Laravel Setup') {
            steps {
                // Gunakan single quotes agar Jenkins tidak mencoba menginterpolasi apapun
                sh 'composer install --no-interaction --prefer-dist'
                sh 'php artisan key:generate --force'
            }
        }

        stage('Testing') {
            steps {
                sh 'php artisan test'
            }
        }
    }
}
